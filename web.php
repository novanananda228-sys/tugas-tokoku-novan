<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TokoController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::middleware('role:admin')->group(function () {
        Route::get('/', [TokoController::class, 'dashboard'])->name('dashboard');

        Route::get('/kategori', [TokoController::class, 'kategori'])->name('kategori.index');
        Route::post('/kategori', [TokoController::class, 'simpanKategori'])->name('kategori.store');
        Route::put('/kategori/{id}', [TokoController::class, 'perbaruiKategori'])->name('kategori.update');
        Route::delete('/kategori/{id}', [TokoController::class, 'hapusKategori'])->name('kategori.destroy');

        Route::get('/produk', [TokoController::class, 'produk'])->name('produk.index');
        Route::post('/produk', [TokoController::class, 'simpanProduk'])->name('produk.store');
        Route::put('/produk/{id}', [TokoController::class, 'perbaruiProduk'])->name('produk.update');
        Route::delete('/produk/{id}', [TokoController::class, 'hapusProduk'])->name('produk.destroy');

        Route::get('/laporan', [TokoController::class, 'laporan'])->name('laporan.index');
    });

    Route::middleware('role:admin,kasir')->group(function () {
        Route::get('/pelanggan', [TokoController::class, 'pelanggan'])->name('pelanggan.index');
        Route::post('/pelanggan', [TokoController::class, 'simpanPelanggan'])->name('pelanggan.store');
        Route::put('/pelanggan/{id}', [TokoController::class, 'perbaruiPelanggan'])->name('pelanggan.update');
        Route::delete('/pelanggan/{id}', [TokoController::class, 'hapusPelanggan'])->name('pelanggan.destroy');

        Route::get('/transaksi', [TokoController::class, 'transaksi'])->name('transaksi.index');
        Route::post('/transaksi', [TokoController::class, 'simpanTransaksi'])->name('transaksi.store');
        Route::get('/kasir', [TokoController::class, 'kasir'])->name('kasir.index');
        Route::get('/cetak-struk', [TokoController::class, 'daftarStruk'])->name('struk.index');
        Route::get('/struk/{id_transaksi?}', [TokoController::class, 'struk'])->name('struk.cetak');
    });

    Route::middleware('role:user')->group(function () {
        Route::get('/user', [TokoController::class, 'user'])->name('user.index');
        Route::post('/keranjang/{id_produk}', [TokoController::class, 'tambahKeranjang'])->name('user.cart.add');
        Route::put('/keranjang/{id_produk}', [TokoController::class, 'perbaruiKeranjang'])->name('user.cart.update');
        Route::delete('/keranjang/{id_produk}', [TokoController::class, 'hapusKeranjang'])->name('user.cart.destroy');
        Route::post('/checkout', [TokoController::class, 'checkoutUser'])->name('user.checkout');
    });
});