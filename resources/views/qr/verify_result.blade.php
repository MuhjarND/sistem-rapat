@extends('layouts.app')

@section('content')
<div class="container" style="max-width:720px">
  @if($valid)
    <div class="alert alert-success">
      <b>✔ QR TERVEFIKASI</b><br>
      Dokumen valid dan sesuai catatan sistem.
    </div>
    <div class="card">
      <div class="card-body">
        <table class="table table-sm">
          <tr><th style="width:180px">Jenis Dokumen</th><td>{{ $summary['doc_type'] }}</td></tr>
          <tr><th>Nomor</th><td>{{ $summary['nomor'] }}</td></tr>
          <tr><th>Judul</th><td>{{ $summary['judul'] }}</td></tr>
          <tr><th>Tanggal</th><td>{{ $summary['tanggal'] }}</td></tr>
          <tr><th>Approver</th><td>{{ $summary['approver'] }}</td></tr>
          <tr><th>Urutan</th><td>{{ $summary['order'] }}</td></tr>
          <tr><th>Waktu Tanda Tangan</th><td>{{ $summary['signed_at'] }}</td></tr>
          <tr><th>Berkas QR</th><td>{{ $summary['file_qr_ok'] }}</td></tr>
        </table>
      </div>
    </div>
  @else
    <div class="alert alert-danger">
      <b>✖ QR TIDAK VALID</b><br>
      {{ $reason }}
    </div>
  @endif

  <div class="mt-3 text-muted" style="font-size:12px">
    <i>Tips:</i> Pastikan URL ini berasal dari domain resmi instansi Anda.
  </div>
</div>
@endsection
