@extends('layouts.app')
@section('title','Detail Rapat')

@section('content')
<div class="card">
  <div class="card-header d-flex align-items-center">
    <strong>Detail Rapat</strong>
    <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-light ml-auto">
      <i class="fas fa-arrow-left mr-1"></i> Kembali
    </a>
  </div>

  <div class="card-body">
    <h4 class="mb-1">{{ $rapat->judul }}</h4>

    <div class="text-muted mb-3">
      {{ \Carbon\Carbon::parse($rapat->tanggal)->isoFormat('dddd, D MMMM Y') }}
      • {{ $rapat->waktu_mulai }} WIT • {{ $rapat->tempat }}
    </div>

    <div class="row">
      <div class="col-md-6">
        <p class="mb-1"><b>Nomor Undangan:</b> {{ $rapat->nomor_undangan ?? '-' }}</p>
        <p class="mb-1"><b>Kategori:</b> {{ $rapat->nama_kategori ?? '-' }}</p>
        <p class="mb-1"><b>Pimpinan:</b> {{ $rapat->nama_pimpinan ?? '-' }} ({{ $rapat->jabatan_pimpinan ?? '-' }})</p>
      </div>
      <div class="col-md-6">
        <p class="mb-1"><b>Tanggal:</b> {{ \Carbon\Carbon::parse($rapat->tanggal)->format('d/m/Y') }}</p>
        <p class="mb-1"><b>Waktu Mulai:</b> {{ $rapat->waktu_mulai }} WIT</p>
        <p class="mb-1"><b>Tempat:</b> {{ $rapat->tempat }}</p>
      </div>
    </div>

    <hr>

    <div class="d-flex flex-wrap">
      {{-- Konfirmasi / Isi Absensi -> ke absensi.scan (GET) jika ada token, fallback ke form peserta --}}
      <a href="{{ !empty($rapat->token_qr) ? route('absensi.scan', $rapat->token_qr) : route('peserta.absensi', $rapat->id) }}"
         class="btn btn-primary mr-2 mb-2">
        <i class="fas fa-qrcode mr-1"></i> Konfirmasi / Isi Absensi
      </a>

      {{-- (Opsional) tombol lihat notulensi jika ingin ditampilkan di sini
      @if(!empty($rapat->id) && \DB::table('notulensi')->where('id_rapat',$rapat->id)->exists())
        <a href="{{ route('peserta.notulensi.show', $rapat->id) }}" class="btn btn-info mr-2 mb-2">
          <i class="fas fa-book-open mr-1"></i> Lihat Notulensi
        </a>
      @endif
      --}}
    </div>

    <p class="text-muted mt-3 mb-0" style="font-size:.9rem">
      * Tombol di atas membuka halaman scan berbasis token (seperti scan QR). Setelah itu, penyimpanan absensi
      dilakukan oleh <code>AbsensiController@simpanScan</code> melalui route
      <code>absensi.scan.save</code> (POST).
    </p>
  </div>
</div>
@endsection
