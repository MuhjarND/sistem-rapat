<?php

use App\Http\Controllers\LaporanController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

//--------------------------------------------------------

//DASHBOARD
Route::middleware(['auth'])->group(function() {
    Route::get('/dashboard/admin', 'DashboardController@admin')->name('dashboard.admin')->middleware('cekrole:admin');
    Route::get('/dashboard/notulis', 'DashboardController@notulis')->name('dashboard.notulis')->middleware('cekrole:notulis');
    Route::get('/dashboard/peserta', 'DashboardController@peserta')->name('dashboard.peserta')->middleware('cekrole:peserta');
    Route::get('/dashboard', 'DashboardController@index')->name('dashboard');
});

// ADMIN
Route::middleware(['auth', 'cekrole:admin'])->group(function () {
    //RAPAT
    Route::resource('rapat', 'RapatController');
    Route::resource('undangan', 'UndanganController');
    Route::get('rapat/{id}/undangan-pdf', 'RapatController@undanganPdf')->name('rapat.undangan.pdf');
    Route::post('rapat/{id}/batal', 'RapatController@batalkan')->name('rapat.batal');
    Route::resource('absensi', 'AbsensiController');
    //INPUT DATA
    Route::resource('pimpinan', 'PimpinanRapatController');
    Route::resource('user', 'UserController');
    Route::resource('kategori', 'KategoriRapatController');
    //LAPORAN
    Route::get('laporan', 'LaporanController@index')->name('laporan.index');
    Route::get('laporan/cetak', 'LaporanController@cetak')->name('laporan.cetak');
    Route::get('laporan/rapat/{id}/gabungan', 'LaporanController@cetakGabunganRapat')->name('laporan.gabungan');
    Route::post('laporan/upload', 'LaporanController@storeUpload')->name('laporan.upload');
    Route::put('/laporan/update/{id}', 'LaporanController@updateFile')->name('laporan.updateFile');
    Route::get('laporan/file/{id}/download', 'LaporanController@downloadFile')->name('laporan.file.download');
    Route::delete('laporan/file/{id}', 'LaporanController@destroyFile')->name('laporan.file.destroy');
    Route::get('/laporan/arsip', 'LaporanController@arsip')->name('laporan.arsip');
    Route::post('/laporan/file/{id}/archive', 'LaporanController@archiveFile')->name('laporan.file.archive');
    Route::post('/laporan/file/{id}/unarchive', 'LaporanController@unarchiveFile')->name('laporan.file.unarchive');
    Route::post('/laporan/rapat/{id}/archive', 'LaporanController@archiveRapat')->name('laporan.rapat.archive');
    
});

//NOTULENSI
Route::middleware(['auth', 'cekrole:admin,notulis'])->group(function () {
    Route::get('absensi/laporan/{id_rapat}', 'AbsensiController@exportPdf')->name('absensi.export.pdf');
    //NOTULENSI
    Route::get('notulensi',                 'NotulensiController@index')->name('notulensi.index');
    Route::get('/notulensi/belum',          'NotulensiController@belum')->name('notulensi.belum');  
    Route::get('/notulensi/sudah',          'NotulensiController@sudah')->name('notulensi.sudah');
    Route::get('notulensi/buat/{id_rapat}', 'NotulensiController@create')->name('notulensi.create');
    Route::post('notulensi',                'NotulensiController@store')->name('notulensi.store');
    Route::get('notulensi/{id}',            'NotulensiController@show')->name('notulensi.show');
    Route::get('notulensi/{id}/edit',       'NotulensiController@edit')->name('notulensi.edit');
    Route::put('notulensi/{id}',            'NotulensiController@update')->name('notulensi.update');
    Route::get('notulensi/{id}/cetak', 'NotulensiController@cetakGabung')->name('notulensi.cetak');
});

// PESERTA
Route::middleware(['auth', 'cekrole:peserta'])->group(function () {
    Route::get('undangan-saya', 'UndanganController@undanganSaya')->name('undangan.saya');
    Route::get('absensi-saya', 'AbsensiController@absensiSaya')->name('absensi.saya');
    Route::post('absensi/isi', 'AbsensiController@isiAbsensi')->name('absensi.isi');
    Route::get('absensi/scan/{token}', 'AbsensiController@scan')->name('absensi.scan');        
    Route::post('absensi/scan/{token}', 'AbsensiController@simpanScan')->name('absensi.scan.save'); 
});


