<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserLookupController;


Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

//--------------------------------------------------------

//PUBLIK
Route::get('/qr/verify', 'VerificationController@verify')->name('qr.verify');

//HARUS LOGIN
Route::get('/users/search', function(\Illuminate\Http\Request $r){
    $q = trim($r->get('q',''));
    return DB::table('users')
        ->when($q, fn($qq)=>$qq->where('name','like',"%{$q}%"))
        ->select('id','name','jabatan','unit')
        ->orderBy('name')->limit(30)->get();
})->name('users.search')->middleware('auth');


Route::middleware(['auth'])->group(function () {
    Route::get('/ajax/users/search', [UserLookupController::class, 'search'])
        ->name('users.search');
});


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

    //ABSENSI
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
    Route::get('/laporan/file/{id}/preview', 'LaporanController@previewFile')->name('laporan.file.preview');
    
});

//NOTULENSI
Route::middleware(['auth', 'cekrole:admin,notulis'])->group(function () {
    //CETAK ABSENSI
    // Route::get('absensi/laporan/{id_rapat}', 'AbsensiController@exportPdf')->name('absensi.export.pdf');
    //NOTULENSI
    Route::get('notulensi',                 'NotulensiController@index')->name('notulensi.index');
    Route::get('/notulensi/dashboard', 'NotulensiController@dashboard')->name('notulensi.dashboard');
    Route::get('/notulensi/belum',          'NotulensiController@belum')->name('notulensi.belum');  
    Route::get('/notulensi/sudah',          'NotulensiController@sudah')->name('notulensi.sudah');
    Route::get('notulensi/buat/{id_rapat}', 'NotulensiController@create')->name('notulensi.create');
    Route::post('notulensi',                'NotulensiController@store')->name('notulensi.store');
    Route::get('notulensi/{id}',            'NotulensiController@show')->name('notulensi.show');
    Route::get('notulensi/{id}/edit',       'NotulensiController@edit')->name('notulensi.edit');
    Route::put('notulensi/{id}',            'NotulensiController@update')->name('notulensi.update');
    //cetak pdf
    // Route::get('notulensi/{id}/cetak', 'NotulensiController@cetakGabung')->name('notulensi.cetak');
    Route::get('/notulensi/{id}/export', 'NotulensiController@exportPdf')->name('notulensi.export');
    //tag user
    Route::get('/users/search', 'UserController@search')->name('users.search');
    //revisi berkas 
    Route::post('/approval/reopen', 'ApprovalController@reopen')->name('approval.reopen');
});

// PESERTA
Route::middleware(['auth', 'cekrole:admin,peserta,approval'])->group(function () {
    Route::get('undangan-saya', 'UndanganController@undanganSaya')->name('undangan.saya');
    Route::get('absensi-saya', 'AbsensiController@absensiSaya')->name('absensi.saya');
    Route::post('absensi/isi', 'AbsensiController@isiAbsensi')->name('absensi.isi');
    Route::get('absensi/scan/{token}', 'AbsensiController@scan')->name('absensi.scan');        
    Route::post('absensi/scan/{token}', 'AbsensiController@simpanScan')->name('absensi.scan.save'); 

    //Cetak undangan
    Route::get('rapat/{id}/undangan-pdf', 'RapatController@undanganPdf')->name('rapat.undangan.pdf');
    
    Route::get('/peserta/dashboard', 'PesertaController@dashboard')->name('peserta.dashboard');
    Route::get('/peserta/rapat', 'PesertaController@rapat')->name('peserta.rapat');

    // >>> tambahan untuk tombol yang kamu minta
    Route::get('/peserta/rapat/{id}', 'PesertaController@showRapat')->name('peserta.rapat.show');          // Detail Rapat
    Route::get('/peserta/rapat/{id}/absensi', 'PesertaController@absensi')->name('peserta.absensi');        // Konfirmasi (form isi)
    Route::get('/peserta/notulensi/{id}', 'PesertaController@showNotulensi')->name('peserta.notulensi.show'); // Lihat Notulensi

    //tag tugas peserta
    Route::put('/peserta/tugas/{id}', 'PesertaController@tugasUpdateStatus')->name('peserta.tugas.update');
    Route::get('/peserta/tugas', 'PesertaController@tugasIndex')->name('peserta.tugas.index');
    Route::put('/peserta/tugas/{id}', 'PesertaController@tugasUpdateStatus')->name('peserta.tugas.update');
});

Route::middleware(['auth', 'cekrole:approval,notulis,admin'])->group(function () {
    Route::get('/approval', 'ApprovalController@dashboard')->name('approval.dashboard');
    Route::get('/approval/pending', 'ApprovalController@pending')->name('approval.pending');
    Route::get('/approval/sign/{token}', 'ApprovalController@signForm')->name('approval.sign');
    Route::post('/approval/sign/{token}', 'ApprovalController@signSubmit')->name('approval.sign.submit');
    Route::get('/approval/done/{token}', 'ApprovalController@done')->name('approval.done');
    Route::get('/approval/history',   'ApprovalController@history')->name('approval.history');
    Route::get('/approval/approved',  'ApprovalController@approved')->name('approval.approved');

    // rapat
    Route::get('/approval/rapat', 'ApprovalController@meetings')->name('approval.rapat'); // halaman rapat (versi approver)
    Route::get('rapat/{id}/show', 'RapatController@show')->name('rapat.show');
    // preview
    Route::get('absensi/laporan/{id_rapat}', 'AbsensiController@exportPdf')->name('absensi.export.pdf');
    Route::get('notulensi/{id}/cetak', 'NotulensiController@cetakGabung')->name('notulensi.cetak');
});

//GUEST (TAMU)
Route::get ('/absensi/guest/{rapat}/{token}', 'AbsensiController@guestForm')->name('absensi.guest.form');
Route::post('/absensi/guest/{rapat}/{token}', 'AbsensiController@guestSubmit')
    ->name('absensi.guest.submit')
    ->middleware('throttle:30,1'); // rate-limit 30 req/menit