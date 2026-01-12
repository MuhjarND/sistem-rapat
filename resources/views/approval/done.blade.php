{{-- resources/views/approval/done.blade.php --}}
@extends('layouts.app')
@section('title','Approval Selesai')

@section('style')
<style>
  .box{
    text-align:center;
    border-radius:16px;
    padding:2rem 1rem;
  }
  .box i{font-size:3rem;margin-bottom:.5rem}

  .box-approved{
    background:linear-gradient(180deg,rgba(34,197,94,.1),rgba(34,197,94,.05));
    border:1px solid rgba(34,197,94,.4);
    color:#dcfce7;
  }
  .box-approved i{color:#22c55e}

  .box-rejected{
    background:linear-gradient(180deg,rgba(239,68,68,.12),rgba(239,68,68,.06));
    border:1px solid rgba(239,68,68,.45);
    color:#fecaca;
  }
  .box-rejected i{color:#ef4444}

  .note{
    border-radius:12px;
    padding:1rem 1.2rem;
    background:rgba(239,68,68,.08);
    border:1px solid rgba(239,68,68,.25);
    color:#fecaca;
  }

  .badge-chip{
    display:inline-flex;align-items:center;gap:.35rem;
    border-radius:999px;padding:.2rem .6rem;font-weight:800;letter-spacing:.2px;
  }
  .badge-approve{background:#16a34a;color:#fff;border:1px solid rgba(255,255,255,.25)}
  .badge-reject{background:#dc2626;color:#fff;border:1px solid rgba(255,255,255,.25)}
  .badge-pending{background:#f59e0b;color:#111;border:1px solid rgba(255,255,255,.25)}
</style>
@endsection

@section('content')
@php
  $status = strtolower($req->status ?? 'pending'); // approved | rejected | pending
@endphp
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Status Proses Approval</h3>
    <a href="{{ route('approval.dashboard') }}" class="btn btn-outline-light btn-sm">
      <i class="fas fa-home mr-1"></i> Dashboard
    </a>
  </div>

  {{-- Header status --}}
  @if($status === 'approved')
    <div class="box box-approved mb-4">
      <i class="fas fa-check-circle"></i>
      <h4 class="mt-2">Dokumen Berhasil Disetujui</h4>
      <p class="mb-2">Terima kasih, tanda tangan digital Anda telah direkam.</p>
      @if(!empty($req->signed_at))
        <small>Tanggal: {{ \Carbon\Carbon::parse($req->signed_at)->translatedFormat('d F Y H:i') }}</small>
      @endif
    </div>
  @elseif($status === 'rejected')
    <div class="box box-rejected mb-4">
      <i class="fas fa-times-circle"></i>
      <h4 class="mt-2">Dokumen Ditolak</h4>
      <p class="mb-2">Dokumen ini dinyatakan <b>Rejected</b> pada proses approval.</p>
      @if(!empty($req->rejected_at))
        <small>Ditolak pada: {{ \Carbon\Carbon::parse($req->rejected_at)->translatedFormat('d F Y H:i') }}</small>
      @endif
    </div>

    {{-- Catatan penolakan --}}
    @if(!empty($req->rejection_note))
      <div class="note mb-4">
        <div class="d-flex align-items-start">
          <i class="fas fa-sticky-note mr-2 mt-1"></i>
          <div>
            <div style="font-weight:800;letter-spacing:.2px;margin-bottom:.25rem;">Catatan Penolakan</div>
            <div style="white-space:pre-wrap">{{ $req->rejection_note }}</div>
          </div>
        </div>
      </div>
    @endif
  @else
    <div class="box box-approved mb-4" style="background:linear-gradient(180deg,rgba(14,165,233,.12),rgba(14,165,233,.06));border-color:rgba(14,165,233,.45);color:#dbeafe">
      <i class="fas fa-hourglass-half" style="color:#0ea5e9"></i>
      <h4 class="mt-2">Menunggu Proses</h4>
      <p class="mb-0">Approval untuk dokumen ini masih dalam status <b>Pending</b>.</p>
    </div>
  @endif

  {{-- Rincian dokumen --}}
  <div class="card">
    <div class="card-header d-flex align-items-center">
      <b>Rincian Dokumen</b>
      <span class="ml-auto">
        @if($status === 'approved')
          <span class="badge-chip badge-approve"><i class="fas fa-check"></i> Approved</span>
        @elseif($status === 'rejected')
          <span class="badge-chip badge-reject"><i class="fas fa-times"></i> Rejected</span>
        @else
          <span class="badge-chip badge-pending"><i class="fas fa-clock"></i> Pending</span>
        @endif
      </span>
    </div>
    <div class="card-body">
      <table class="table mb-0">
        <tr><th style="width:30%">Judul Rapat</th><td>{{ $rapat->judul ?? '-' }}</td></tr>
        <tr><th>Jenis Dokumen</th><td>{{ ucfirst($req->doc_type) }}</td></tr>
        <tr><th>Nomor Undangan</th><td>{{ $rapat->nomor_undangan ?? '-' }}</td></tr>
        <tr><th>Tanggal Rapat</th><td>{{ \Carbon\Carbon::parse($rapat->tanggal)->translatedFormat('l, d F Y') }} {{ \App\Helpers\TimeHelper::short($rapat->waktu_mulai) }}</td></tr>
        <tr><th>Tempat</th><td>{{ $rapat->tempat }}</td></tr>

        @if($status === 'approved')
          <tr>
            <th>Waktu Persetujuan</th>
            <td>{{ \Carbon\Carbon::parse($req->signed_at)->translatedFormat('d F Y H:i') }}</td>
          </tr>
        @elseif($status === 'rejected')
          <tr>
            <th>Waktu Penolakan</th>
            <td>{{ \Carbon\Carbon::parse($req->rejected_at)->translatedFormat('d F Y H:i') }}</td>
          </tr>
          @if(!empty($req->rejection_note))
          <tr>
            <th>Catatan Penolakan</th>
            <td style="white-space:pre-wrap">{{ $req->rejection_note }}</td>
          </tr>
          @endif
        @endif
      </table>
    </div>
  </div>

  <div class="text-right mt-4">
    <a href="{{ route('approval.pending') }}" class="btn btn-outline-light">
      <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar Pending
    </a>
  </div>
</div>
@endsection


