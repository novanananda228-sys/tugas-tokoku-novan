<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TokoController extends Controller
{
    public function dashboard(): View
    {
        return view('dashboard', [
            'pelanggan' => DB::table('tbl_pelanggan')->count(),
            'produk' => DB::table('tbl_produk')->count(),
            'transaksi' => DB::table('tbl_transaksi')->count(),
            'pendapatan' => DB::table('tbl_transaksi')->sum('total_belanja'),
            'terbaru' => DB::table('tbl_transaksi as t')
                ->leftJoin('tbl_pelanggan as p', 'p.id_pelanggan', '=', 't.id_pelanggan')
                ->leftJoin('users as u', 'u.id', '=', 't.id_user')
                ->select('t.*', DB::raw("COALESCE(p.nama_pelanggan, u.name, 'Pelanggan tidak tersedia') as nama_pelanggan"))
                ->orderByDesc('t.id_transaksi')
                ->limit(6)
                ->get(),
        ]);
    }

    public function pelanggan(): View
    {
        return view('pelanggan.index', [
            'data' => DB::table('tbl_pelanggan')->orderByDesc('id_pelanggan')->get(),
        ]);
    }

    public function simpanPelanggan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_pelanggan' => ['required', 'string', 'max:100'],
            'kota' => ['required', 'string', 'max:50'],
            'alamat' => ['nullable', 'string'],
            'no_hp' => ['nullable', 'string', 'max:20'],
        ]);

        DB::table('tbl_pelanggan')->insert($data);

        return to_route('pelanggan.index')->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    public function perbaruiPelanggan(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'nama_pelanggan' => ['required', 'string', 'max:100'],
            'kota' => ['required', 'string', 'max:50'],
            'alamat' => ['nullable', 'string'],
            'no_hp' => ['nullable', 'string', 'max:20'],
        ]);

        DB::table('tbl_pelanggan')->where('id_pelanggan', $id)->update($data);

        return to_route('pelanggan.index')->with('success', 'Data pelanggan berhasil diperbarui.');
    }

    public function hapusPelanggan(int $id): RedirectResponse
    {
        if (DB::table('tbl_transaksi')->where('id_pelanggan', $id)->exists()) {
            return back()->with('error', 'Pelanggan yang sudah memiliki transaksi tidak dapat dihapus.');
        }

        DB::table('tbl_pelanggan')->where('id_pelanggan', $id)->delete();

        return to_route('pelanggan.index')->with('success', 'Pelanggan berhasil dihapus.');
    }

    public function kategori(): View
    {
        return view('kategori.index', [
            'data' => DB::table('tbl_kategori')->orderByDesc('id_kategori')->get(),
        ]);
    }

    public function simpanKategori(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_kategori' => ['required', 'string', 'max:100'],
        ]);

        DB::table('tbl_kategori')->insert($data);

        return to_route('kategori.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function perbaruiKategori(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'nama_kategori' => ['required', 'string', 'max:100'],
        ]);

        DB::table('tbl_kategori')->where('id_kategori', $id)->update($data);

        return to_route('kategori.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function hapusKategori(int $id): RedirectResponse
    {
        if (DB::table('tbl_produk')->where('id_kategori', $id)->exists()) {
            return back()->with('error', 'Kategori yang masih digunakan produk tidak dapat dihapus.');
        }

        DB::table('tbl_kategori')->where('id_kategori', $id)->delete();

        return to_route('kategori.index')->with('success', 'Kategori berhasil dihapus.');
    }

    public function produk(): View
    {
        return view('produk.index', [
            'data' => DB::table('tbl_produk as p')
                ->leftJoin('tbl_kategori as k', 'k.id_kategori', '=', 'p.id_kategori')
                ->select('p.*', 'k.nama_kategori')
                ->orderByDesc('p.id_produk')
                ->get(),
            'kategori' => DB::table('tbl_kategori')->orderBy('nama_kategori')->get(),
        ]);
    }

    public function simpanProduk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id_kategori' => ['required', 'integer', 'exists:tbl_kategori,id_kategori'],
            'nama_produk' => ['required', 'string', 'max:100'],
            'kode_barcode' => ['nullable', 'string', 'max:100', 'unique:tbl_produk,kode_barcode'],
            'harga' => ['required', 'numeric', 'min:0'],
            'stok' => ['required', 'integer', 'min:0'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('produk', 'public');
        }

        DB::table('tbl_produk')->insert($data);

        return to_route('produk.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function perbaruiProduk(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'id_kategori' => ['required', 'integer', 'exists:tbl_kategori,id_kategori'],
            'nama_produk' => ['required', 'string', 'max:100'],
            'kode_barcode' => ['nullable', 'string', 'max:100', 'unique:tbl_produk,kode_barcode,' . $id . ',id_produk'],
            'harga' => ['required', 'numeric', 'min:0'],
            'stok' => ['required', 'integer', 'min:0'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $product = DB::table('tbl_produk')->where('id_produk', $id)->first();

        if ($request->hasFile('foto')) {
            $newPhoto = $request->file('foto')->store('produk', 'public');
            $data['foto'] = $newPhoto;
        }

        DB::table('tbl_produk')->where('id_produk', $id)->update($data);

        if (isset($newPhoto) && $product?->foto) {
            Storage::disk('public')->delete($product->foto);
        }

        return to_route('produk.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function hapusProduk(int $id): RedirectResponse
    {
        if (DB::table('tbl_detail_transaksi')->where('id_produk', $id)->exists()) {
            return back()->with('error', 'Produk yang sudah tercatat dalam transaksi tidak dapat dihapus.');
        }

        $product = DB::table('tbl_produk')->where('id_produk', $id)->first();
        DB::table('tbl_produk')->where('id_produk', $id)->delete();

        if ($product?->foto) {
            Storage::disk('public')->delete($product->foto);
        }

        return to_route('produk.index')->with('success', 'Produk berhasil dihapus.');
    }

    public function transaksi(): View
    {
        return view('transaksi.index', [
            'pelanggan' => DB::table('tbl_pelanggan')->orderBy('nama_pelanggan')->get(),
            'produk' => DB::table('tbl_produk')->where('stok', '>', 0)->orderBy('nama_produk')->get(),
            'data' => DB::table('tbl_transaksi as t')
                ->leftJoin('tbl_pelanggan as p', 'p.id_pelanggan', '=', 't.id_pelanggan')
                ->leftJoin('users as u', 'u.id', '=', 't.id_user')
                ->select('t.*', DB::raw("COALESCE(p.nama_pelanggan, u.name, 'Pelanggan dihapus') as nama_pelanggan"))
                ->orderByDesc('t.id_transaksi')
                ->limit(100)
                ->get(),
        ]);
    }

    public function kasir(): View
    {
        return view('kasir.index', [
            'pelanggan' => DB::table('tbl_pelanggan')->orderBy('nama_pelanggan')->get(),
            'produk' => DB::table('tbl_produk')
                ->where('stok', '>', 0)
                ->orderBy('nama_produk')
                ->get(),
        ]);
    }

    public function user(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'id_kategori' => ['nullable', 'integer', 'exists:tbl_kategori,id_kategori'],
        ]);
        $cart = $this->keranjangUser((int) $request->user()->id);
        $cartProducts = empty($cart)
            ? collect()
            : DB::table('tbl_produk as p')
                ->leftJoin('tbl_kategori as k', 'k.id_kategori', '=', 'p.id_kategori')
                ->whereIn('p.id_produk', array_keys($cart))
                ->select('p.id_produk', 'p.nama_produk', 'p.harga', 'p.stok', 'p.foto', 'k.nama_kategori')
                ->get()
                ->keyBy('id_produk');
        $cartItems = collect();

        foreach ($cart as $productId => $quantity) {
            $item = $cartProducts->get((int) $productId);
            $unavailable = ! $item;

            if ($unavailable) {
                $item = (object) [
                    'id_produk' => (int) $productId,
                    'nama_produk' => 'Produk sudah tidak tersedia',
                    'harga' => 0,
                    'stok' => 0,
                    'foto' => null,
                    'nama_kategori' => null,
                ];
            }

            $item->jumlah = (int) $quantity;
            $item->subtotal = $item->harga * $item->jumlah;
            $item->tidak_tersedia = $unavailable;
            $cartItems->push($item);
        }

        return view('user', [
            'data' => DB::table('tbl_produk as p')
                ->leftJoin('tbl_kategori as k', 'k.id_kategori', '=', 'p.id_kategori')
                ->select('p.id_produk', 'p.id_kategori', 'p.nama_produk', 'p.harga', 'p.stok', 'p.foto', 'k.nama_kategori')
                ->where('p.stok', '>', 0)
                ->when($filters['q'] ?? null, function ($query, $search): void {
                    $query->where('p.nama_produk', 'like', '%' . $search . '%');
                })
                ->when($filters['id_kategori'] ?? null, function ($query, $categoryId): void {
                    $query->where('p.id_kategori', $categoryId);
                })
                ->orderBy('p.nama_produk')
                ->get(),
            'kategori' => DB::table('tbl_kategori')->orderBy('nama_kategori')->get(),
            'filters' => $filters,
            'cartItems' => $cartItems,
            'cartTotal' => $cartItems->sum('subtotal'),
            'canCheckout' => $cartItems->isNotEmpty() && $cartItems->every(
                fn ($item): bool => ! $item->tidak_tersedia && $item->jumlah <= $item->stok
            ),
        ]);
    }

    public function tambahKeranjang(Request $request, int $id_produk): RedirectResponse
    {
        $data = $request->validate([
            'jumlah' => ['required', 'integer', 'min:1'],
        ]);
        $product = DB::table('tbl_produk')->where('id_produk', $id_produk)->first();

        if (! $product) {
            abort(404, 'Produk tidak ditemukan.');
        }

        $userId = (int) $request->user()->id;
        $cart = $this->keranjangUser($userId);
        $quantity = (int) ($cart[$id_produk] ?? 0) + $data['jumlah'];

        if ($quantity > $product->stok) {
            return back()->withInput()->with('error', 'Jumlah melebihi stok produk yang tersedia.');
        }

        $cart[$id_produk] = $quantity;
        $this->simpanKeranjangUser($userId, $cart);

        return to_route('user.index')->with('success', 'Produk berhasil dimasukkan ke keranjang.');
    }

    public function perbaruiKeranjang(Request $request, int $id_produk): RedirectResponse
    {
        $data = $request->validate([
            'jumlah' => ['required', 'integer', 'min:1'],
        ]);
        $userId = (int) $request->user()->id;
        $cart = $this->keranjangUser($userId);

        if (! array_key_exists($id_produk, $cart)) {
            return back()->with('error', 'Produk tidak ditemukan di keranjang.');
        }

        $product = DB::table('tbl_produk')->where('id_produk', $id_produk)->first();

        if (! $product || $data['jumlah'] > $product->stok) {
            return back()->withInput()->with('error', 'Jumlah melebihi stok atau produk sudah tidak tersedia.');
        }

        $cart[$id_produk] = $data['jumlah'];
        $this->simpanKeranjangUser($userId, $cart);

        return to_route('user.index')->with('success', 'Jumlah produk di keranjang berhasil diperbarui.');
    }

    public function hapusKeranjang(Request $request, int $id_produk): RedirectResponse
    {
        $userId = (int) $request->user()->id;
        $cart = $this->keranjangUser($userId);

        if (! array_key_exists($id_produk, $cart)) {
            return back()->with('error', 'Produk tidak ditemukan di keranjang.');
        }

        unset($cart[$id_produk]);
        $this->simpanKeranjangUser($userId, $cart);

        return to_route('user.index')->with('success', 'Produk berhasil dihapus dari keranjang.');
    }

    public function checkoutUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'metode_pembayaran' => ['required', 'in:tunai,qris'],
            'konfirmasi_pembayaran' => ['exclude_unless:metode_pembayaran,qris', 'required', 'accepted'],
        ]);
        $userId = (int) $request->user()->id;
        $cart = $this->keranjangUser($userId);

        if (empty($cart)) {
            return to_route('user.index')->with('error', 'Keranjang masih kosong.');
        }

        $transactionId = $this->catatTransaksi($cart, null, $data['metode_pembayaran'], $userId);

        if (! $transactionId) {
            return to_route('user.index')->with('error', 'Stok berubah atau produk tidak tersedia. Periksa kembali keranjang Anda.');
        }

        $this->simpanKeranjangUser($userId, []);

        return to_route('user.index')->with(
            'success',
            'Pembelian berhasil. Nomor transaksi #' . $transactionId . ' — pembayaran ' . strtoupper($data['metode_pembayaran']) . '.'
        );
    }

    public function simpanTransaksi(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id_pelanggan' => ['required', 'integer', 'exists:tbl_pelanggan,id_pelanggan'],
            'id_produk' => ['required', 'integer', 'exists:tbl_produk,id_produk'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'metode_pembayaran' => ['nullable', 'in:tunai,qris'],
            'konfirmasi_pembayaran' => ['exclude_unless:metode_pembayaran,qris', 'required', 'accepted'],
        ]);
        $data['metode_pembayaran'] = $data['metode_pembayaran'] ?? 'tunai';

        $transactionId = $this->catatTransaksi(
            [$data['id_produk'] => $data['jumlah']],
            $data['id_pelanggan'],
            $data['metode_pembayaran']
        );

        if (! $transactionId) {
            return back()->withInput()->with('error', 'Stok produk tidak mencukupi.');
        }

        return to_route('struk.cetak', ['id_transaksi' => $transactionId])
            ->with('success', 'Transaksi berhasil disimpan dan stok diperbarui.');
    }

    private function keranjangUser(int $userId): array
    {
        $cart = session()->get('keranjang_user_' . $userId, []);

        return is_array($cart) ? $cart : [];
    }

    private function simpanKeranjangUser(int $userId, array $cart): void
    {
        session()->put('keranjang_user_' . $userId, $cart);
    }

    private function catatTransaksi(array $items, ?int $customerId, string $paymentMethod, ?int $userId = null): ?int
    {
        if ($items === []) {
            return null;
        }

        return DB::transaction(function () use ($items, $customerId, $paymentMethod, $userId): ?int {
            $productIds = array_map('intval', array_keys($items));
            sort($productIds);
            $products = DB::table('tbl_produk')
                ->whereIn('id_produk', $productIds)
                ->orderBy('id_produk')
                ->lockForUpdate()
                ->get()
                ->keyBy('id_produk');

            if ($products->count() !== count($productIds)) {
                return null;
            }

            $details = [];
            $total = 0;
            foreach ($items as $productId => $quantity) {
                $product = $products->get((int) $productId);
                $quantity = (int) $quantity;

                if (! $product || $quantity < 1 || $product->stok < $quantity) {
                    return null;
                }

                $subtotal = $product->harga * $quantity;
                $total += $subtotal;
                $details[] = [
                    'id_produk' => $product->id_produk,
                    'jumlah' => $quantity,
                    'harga' => $product->harga,
                    'subtotal' => $subtotal,
                ];
            }

            $transactionId = DB::table('tbl_transaksi')->insertGetId([
                'tanggal' => now(),
                'id_pelanggan' => $customerId,
                'id_user' => $userId,
                'total_belanja' => $total,
                'metode_pembayaran' => $paymentMethod,
            ]);

            DB::table('tbl_detail_transaksi')->insert(array_map(
                fn (array $detail): array => ['id_transaksi' => $transactionId] + $detail,
                $details
            ));

            foreach ($details as $detail) {
                DB::table('tbl_produk')
                    ->where('id_produk', $detail['id_produk'])
                    ->decrement('stok', $detail['jumlah']);
            }

            DB::table('tbl_log')->insert([
                'aktivitas' => 'TRANSAKSI BARU',
                'keterangan' => 'Transaksi #' . $transactionId,
            ]);

            return $transactionId;
        });
    }

    public function struk(?int $id_transaksi = null): View
    {
        $transaction = DB::table('tbl_transaksi as t')
            ->leftJoin('tbl_pelanggan as p', 'p.id_pelanggan', '=', 't.id_pelanggan')
            ->leftJoin('users as u', 'u.id', '=', 't.id_user')
            ->select('t.*', DB::raw("COALESCE(p.nama_pelanggan, u.name, 'Pelanggan dihapus') as nama_pelanggan"))
            ->when($id_transaksi, fn ($query) => $query->where('t.id_transaksi', $id_transaksi))
            ->unless($id_transaksi, fn ($query) => $query->orderByDesc('t.id_transaksi'))
            ->first();

        abort_if(! $transaction, 404, 'Transaksi tidak ditemukan.');

        $details = DB::table('tbl_detail_transaksi as d')
            ->join('tbl_produk as pr', 'pr.id_produk', '=', 'd.id_produk')
            ->where('d.id_transaksi', $transaction->id_transaksi)
            ->select('pr.nama_produk', 'd.jumlah', 'd.harga', 'd.subtotal')
            ->get();

        return view('struk', [
            'transaksi' => $transaction,
            'detail' => $details,
        ]);
    }

    public function daftarStruk(): View
    {
        return view('struk.index', [
            'data' => DB::table('tbl_transaksi as t')
                ->leftJoin('tbl_pelanggan as p', 'p.id_pelanggan', '=', 't.id_pelanggan')
                ->leftJoin('users as u', 'u.id', '=', 't.id_user')
                ->select('t.*', DB::raw("COALESCE(p.nama_pelanggan, u.name, 'Pelanggan dihapus') as nama_pelanggan"))
                ->orderByDesc('t.id_transaksi')
                ->get(),
        ]);
    }

    public function laporan(Request $request): View
    {
        $filters = $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'produk' => ['nullable', 'string', 'max:100'],
        ]);

        $details = DB::table('tbl_transaksi as t')
            ->join('tbl_detail_transaksi as d', 'd.id_transaksi', '=', 't.id_transaksi')
            ->join('tbl_produk as pr', 'pr.id_produk', '=', 'd.id_produk')
            ->leftJoin('tbl_pelanggan as p', 'p.id_pelanggan', '=', 't.id_pelanggan')
            ->leftJoin('users as u', 'u.id', '=', 't.id_user');

        if (! empty($filters['dari'])) {
            $transactions->whereDate('tanggal', '>=', $filters['dari']);
            $details->whereDate('t.tanggal', '>=', $filters['dari']);
        }

        if (! empty($filters['sampai'])) {
            $details->whereDate('t.tanggal', '<=', $filters['sampai']);
        }

        if (! empty($filters['produk'])) {
            $details->where('pr.nama_produk', 'like', '%' . $filters['produk'] . '%');
        }

        $jumlahProdukTerjual = (clone $details)->sum('d.jumlah');
        $totalPenjualan = (clone $details)->sum('d.subtotal');

        return view('laporan.index', [
            'data' => $details
                ->select('t.id_transaksi', 't.tanggal', DB::raw("COALESCE(p.nama_pelanggan, u.name, 'Pelanggan') as nama_pelanggan"), 'pr.nama_produk', 'd.jumlah', 'd.subtotal')
                ->orderByDesc('t.id_transaksi')
                ->get(),
            'total' => $totalPenjualan,
            'jumlahProdukTerjual' => $jumlahProdukTerjual,
            'filters' => $filters,
        ]);
    }
}
