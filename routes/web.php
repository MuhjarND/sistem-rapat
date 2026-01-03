<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserLookupController;


Route::redirect('/', '/login');

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

Route::middleware(['auth', 'cekrole:admin'])->group(function () {
    //INPUT DATA
    Route::resource('pimpinan', 'PimpinanRapatController');
    Route::resource('user', 'UserController');
    Route::post('user/{id}/send-credentials', 'UserController@sendCredentials')->name('user.sendCredentials');
    Route::post('user/send-credentials-all', 'UserController@sendCredentialsAll')->name('user.sendCredentialsAll');
    Route::post('/users/assign-unit', 'UsersController@assignUnit')->name('users.assignUnit');
    Route::resource('kategori', 'KategoriRapatController');
    Route::get('/units',            'UnitController@index')->name('units.index');
    Route::post('/units',           'UnitController@store')->name('units.store');
    Route::put('/units/{id}',       'UnitController@update')->name('units.update');
    Route::delete('/units/{id}',    'UnitController@destroy')->name('units.destroy');
    Route::post('/units/{id}/toggle','UnitController@toggle')->name('units.toggle');    
    Route::resource('jabatan', 'JabatanController');
    Route::get('/bidang', 'BidangController@index')->name('bidang.index');
    Route::post('/bidang', 'BidangController@store')->name('bidang.store');
    Route::put('/bidang/{id}', 'BidangController@update')->name('bidang.update');
    Route::delete('/bidang/{id}', 'BidangController@destroy')->name('bidang.destroy');
});

// ADMIN
Route::middleware(['auth', 'cekrole:admin,operator'])->group(function () {
    // Jadwal rapat berkala (tidak langsung ke approval)
    Route::get('rapat/jadwal', 'RapatController@scheduleIndex')->name('rapat.schedule.index');
    Route::get('rapat/jadwal/create', 'RapatController@createSchedule')->name('rapat.schedule.create');
    Route::post('rapat/jadwal', 'RapatController@storeSchedule')->name('rapat.schedule.store');
    Route::post('rapat/{id}/send-approval', 'RapatController@sendToApproval')->name('rapat.sendApproval');

    //RAPAT
    Route::resource('rapat', 'RapatController');
    //ABSENSI
    Route::resource('absensi', 'AbsensiController');
    Route::post('/absensi/{id}/wa-start', 'AbsensiController@notifyStart')->name('absensi.notify.start');
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
    Route::get('/laporan/tindak-lanjut', 'LaporanController@tindakLanjut')->name('laporan.tindaklanjut');
    Route::get('/laporan/tindak-lanjut/rapat/{id}/cetak', 'LaporanController@cetakTindakLanjutRapat')->name('laporan.tindaklanjut.cetak');
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
Route::middleware(['auth', 'cekrole:admin,peserta,approval,operator,notulis,protokoler'])->group(function () {
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
    Route::post('/peserta/tugas/{id}/eviden', 'PesertaController@uploadEviden')->name('peserta.tugas.eviden');
});

Route::middleware(['auth', 'cekrole:approval,admin'])->group(function () {
    Route::get('/approval', 'ApprovalController@dashboard')->name('approval.dashboard');
    Route::get('/approval/pending', 'ApprovalController@pending')->name('approval.pending');
    Route::get('/approval/tugas', 'ApprovalController@tasks')->name('approval.tugas');
    Route::post('/approval/tugas/{id}/remind', 'ApprovalController@remindTask')->name('approval.tugas.remind');
    Route::get('/approval/sign/{token}', 'ApprovalController@signForm')->name('approval.sign');
    Route::post('/approval/sign/{token}', 'ApprovalController@signSubmit')->name('approval.sign.submit');
    Route::get('/approval/done/{token}', 'ApprovalController@done')->name('approval.done');
    Route::get('/approval/history',   'ApprovalController@history')->name('approval.history');
    Route::get('/approval/approved',  'ApprovalController@approved')->name('approval.approved');

    // rapat
    Route::get('/approval/rapat', 'ApprovalController@meetings')->name('approval.rapat');
    // preview
    Route::get('absensi/laporan/{id_rapat}', 'AbsensiController@exportPdf')->name('absensi.export.pdf');
    Route::get('notulensi/{id}/cetak', 'NotulensiController@cetakGabung')->name('notulensi.cetak');
});

Route::middleware(['auth', 'cekrole:approval,notulis,admin,operator'])->group(function () {
    // preview
    Route::get('absensi/laporan/{id_rapat}', 'AbsensiController@exportPdf')->name('absensi.export.pdf');
    Route::get('notulensi/{id}/cetak', 'NotulensiController@cetakGabung')->name('notulensi.cetak');
    Route::get('rapat/{id}/show', 'RapatController@show')->name('rapat.show');
});

// PROTOKOLER
Route::middleware(['auth', 'cekrole:protokoler'])->group(function () {
    Route::get('/agenda-pimpinan', 'AgendaPimpinanController@index')->name('agenda-pimpinan.index');
    Route::post('/agenda-pimpinan', 'AgendaPimpinanController@store')->name('agenda-pimpinan.store');
});

//GUEST (TAMU)
Route::get ('/absensi/guest/{rapat}/{token}', 'AbsensiController@guestForm')->name('absensi.guest.form');
Route::post('/absensi/guest/{rapat}/{token}', 'AbsensiController@guestSubmit')
    ->name('absensi.guest.submit')
    ->middleware('throttle:30,1'); // rate-limit 30 req/menit

// ================= Absensi Publik (tanpa login) =================
// routes/web.php
Route::get('/absensi/publik/{token}',       'PublicAbsensiController@show')->name('absensi.publik.show');
Route::get('/absensi/publik/{token}/cari',  'PublicAbsensiController@search')->name('absensi.publik.search');
Route::post('/absensi/publik/{token}',      'PublicAbsensiController@store')->name('absensi.publik.store');
