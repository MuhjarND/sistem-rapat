﻿@extends('layouts.app')

@section('title','Detail Rapat')

@section('content')
<div class="container">

  {{-- Header + tombol unduh --}}
  <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <h3 class="mb-0">Detail Rapat</h3>

    @auth
    @if(in_array(Auth::user()->role, ['admin','notulis','approval','operator']))
      <div class="btn-group">
        <a href="{{ route('absensi.export.pdf', $rapat->id) }}" class="btn btn-primary btn-sm">
          <i class="fas fa-file-pdf mr-1"></i> Laporan Absensi
        </a>
        <a href="{{ route('rapat.undangan.pdf', $rapat->id) }}" class="btn btn-primary btn-sm">
          <i class="fas fa-envelope-open-text mr-1"></i> Undangan
        </a>
      </div>
    @endif
    @endauth
  </div>

  @php
    // Fallback aman untuk URL/QR publik (jika controller belum mengirim variabelnya)
    $qrPublikUrl = $qrPublikUrl
      ?? ( (Route::has('absensi.publik.show') && !empty($rapat->public_code))
            ? route('absensi.publik.show', $rapat->public_code)
            : null );

    $qrPublikImg = $qrPublikImg
      ?? ( $qrPublikUrl ? 'https://api.qrserver.com/v1/create-qr-code/?size=600x600&data='.urlencode($qrPublikUrl) : null );
  @endphp

  <div class="card p-4">
    <div class="row">
      {{-- ===== Kolom KIRI: Info + Preview + Peserta ===== --}}
      <div class="col-lg-7">

        {{-- Ringkasan Rapat --}}
        <div class="mb-3">
          <h5 class="mb-2">Informasi Rapat</h5>
          <div class="border rounded p-3" style="border-color:rgba(226,232,240,.15)!important;">
            <dl class="row mb-0">
              <dt class="col-md-4">Nomor Undangan</dt>
              <dd class="col-md-8">{{ $rapat->nomor_undangan ?: '-' }}</dd>

              <dt class="col-md-4">Judul</dt>
              <dd class="col-md-8 font-weight-bold">{{ $rapat->judul }}</dd>

              <dt class="col-md-4">Deskripsi</dt>
              <dd class="col-md-8">{{ $rapat->deskripsi ?: '-' }}</dd>

              <dt class="col-md-4">Tanggal</dt>
              <dd class="col-md-8">{{ \Carbon\Carbon::parse($rapat->tanggal)->translatedFormat('l, d F Y') }}</dd>

              <dt class="col-md-4">Waktu Mulai</dt>
              <dd class="col-md-8">{{ \App\Helpers\TimeHelper::short($rapat->waktu_mulai) }}</dd>

              <dt class="col-md-4">Tempat</dt>
              <dd class="col-md-8">{{ $rapat->tempat }}</dd>
            </dl>
          </div>
        </div>

        {{-- Preview Dokumen (Undangan & Absensi) --}}
        @auth
        @if(in_array(Auth::user()->role, ['admin','notulis','approval','operator']))
        <div class="mb-4">
          <h5 class="mb-3">Preview Dokumen</h5>

          {{-- Preview Undangan --}}
          <div class="mb-3">
            <div class="d-flex align-items-center mb-2">
              <i class="fas fa-envelope-open-text mr-2"></i>
              <strong>Undangan Rapat</strong>
              <a href="{{ $previewUndanganUrl }}" target="_blank" class="btn btn-sm btn-outline-light ml-auto">
                <i class="fas fa-external-link-alt mr-1"></i> Buka Tab Baru
              </a>
            </div>
            <div class="pdf-frame-wrap">
              <iframe class="pdf-frame" src="{{ $previewUndanganUrl }}" title="Preview Undangan"></iframe>
            </div>
          </div>

          {{-- Preview Absensi --}}
          <div class="mb-2">
            <div class="d-flex align-items-center mb-2">
              <i class="fas fa-file-pdf mr-2"></i>
              <strong>Laporan Absensi</strong>
              <a href="{{ $previewAbsensiUrl }}" target="_blank" class="btn btn-sm btn-outline-light ml-auto">
                <i class="fas fa-external-link-alt mr-1"></i> Buka Tab Baru
              </a>
            </div>
            <div class="pdf-frame-wrap">
              <iframe class="pdf-frame" src="{{ $previewAbsensiUrl }}" title="Preview Absensi"></iframe>
            </div>
          </div>
        </div>
        @endif
        @endauth

        {{-- Peserta Undangan --}}
        <div class="mb-2">
          <h5 class="mb-2">Peserta Undangan</h5>
          <div class="border rounded p-3" style="border-color:rgba(226,232,240,.15)!important; max-height:320px; overflow:auto;">
            @forelse($daftar_peserta as $peserta)
              <div class="mb-1">
                <span class="font-weight-bold">{{ $peserta->name }}</span>
                @if(!empty($peserta->jabatan))
                  <span class="text-muted"> - {{ $peserta->jabatan }}</span>
                @endif
              </div>
            @empty
              <div class="text-muted">Belum ada peserta.</div>
            @endforelse
          </div>
        </div>

      </div>

      {{-- ===== Kolom KANAN: QR Codes ===== --}}
      <div class="col-lg-5">

        @auth
        @if(in_array(Auth::user()->role, ['admin','notulis','operator']))
          {{-- QR Peserta (internal) --}}
          <div class="mb-4">
            <h5 class="mt-1">QR Code Absensi Peserta</h5>
            <p class="text-muted mb-2">Untuk peserta internal (akun sistem).</p>

            <div class="p-3 mb-2 rounded d-inline-block" style="background:rgba(255,255,255,.05);">
              <img src="{{ $qrPesertaImg }}" alt="QR Peserta" style="width:180px;height:auto;border-radius:8px;border:1px solid rgba(226,232,240,.2);background:#fff;">
            </div>
            <div class="mt-2">
              <small class="text-muted d-block">Link langsung:</small>
              <code style="word-break:break-all;">{{ $qrPesertaUrl }}</code>
              <div>
                <button type="button" class="btn btn-sm btn-outline-light mt-2" onclick="copyToClipboard('{{ $qrPesertaUrl }}')">
                  <i class="fas fa-copy mr-1"></i> Salin tautan
                </button>
              </div>
            </div>
          </div>

          {{-- QR Tamu (guest token) --}}
          <div class="mb-4">
            <h5 class="mt-1">QR Code Absensi Tamu</h5>
            <p class="text-muted mb-2">Untuk tamu eksternal tanpa akun.</p>

            <div class="p-3 mb-2 rounded d-inline-block" style="background:rgba(255,255,255,.05);">
              @if(!empty($qrTamuImg))
                <img src="{{ $qrTamuImg }}" alt="QR Tamu" style="width:180px;height:auto;border-radius:8px;border:1px solid rgba(226,232,240,.2);background:#fff;">
              @else
                <div class="d-flex align-items-center justify-content-center text-muted"
                    style="width:180px;height:180px;border-radius:8px;border:1px dashed rgba(226,232,240,.35);">
                  QR tamu tidak tersedia
                </div>
              @endif
            </div>

            @if(!empty($qrTamuUrl))
              <div class="mt-2">
                <small class="text-muted d-block">Link langsung:</small>
                <code style="word-break:break-all;">{{ $qrTamuUrl }}</code>
                <div>
                  <button type="button" class="btn btn-sm btn-outline-light mt-2" onclick="copyToClipboard('{{ $qrTamuUrl }}')">
                    <i class="fas fa-copy mr-1"></i> Salin tautan
                  </button>
                </div>
              </div>
            @endif
          </div>

          {{-- QR Absensi Publik (public_code) --}}
          <div class="mb-1">
            <h5 class="mt-1">Absensi Publik (Tanpa Login)</h5>
            <p class="text-muted mb-2">Peserta memilih nama (dropdown + cari) dan tanda tangan digital.</p>

            <div class="p-3 mb-2 rounded d-inline-block" style="background:rgba(255,255,255,.05);">
              @if(!empty($qrPublikImg))
                <img src="{{ $qrPublikImg }}" alt="QR Absensi Publik" style="width:180px;height:auto;border-radius:8px;border:1px solid rgba(226,232,240,.2);background:#fff;">
              @else
                <div class="d-flex align-items-center justify-content-center text-muted"
                    style="width:180px;height:180px;border-radius:8px;border:1px dashed rgba(226,232,240,.35);">
                  QR publik tidak tersedia
                </div>
              @endif
            </div>

            @if(!empty($qrPublikUrl))
              <div class="mt-2">
                <small class="text-muted d-block">Link langsung:</small>
                <code style="word-break:break-all;">{{ $qrPublikUrl }}</code>
                <div>
                  <button type="button" class="btn btn-sm btn-outline-light mt-2" onclick="copyToClipboard('{{ $qrPublikUrl }}')">
                    <i class="fas fa-copy mr-1"></i> Salin tautan
                  </button>
                </div>
              </div>
              <div class="text-muted" style="font-size:.85rem;margin-top:.5rem;">
                Jabatan & unit akan terisi otomatis pada laporan.
              </div>
            @endif
          </div>
        @endif
        @endauth

      </div>
    </div>

    {{-- Tombol Kembali --}}
    <div class="mt-4">
      <a href="{{ route('rapat.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Kembali
      </a>
    </div>
  </div>
</div>

{{-- Script salin ke clipboard --}}
@push('scripts')
<script>
  function copyToClipboard(text){
    if (!navigator.clipboard) {
      const ta = document.createElement('textarea');
      ta.value = text; document.body.appendChild(ta);
      ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
      alert('Tautan disalin.');
      return;
    }
    navigator.clipboard.writeText(text).then(()=>alert('Tautan disalin.'));
  }
</script>
@endpush

@push('styles')
<style>
  .pdf-frame-wrap{
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(226,232,240,.15);
    border-radius: 8px;
    overflow: hidden;
  }
  .pdf-frame{
    width: 100%;
    height: 420px;
    border: 0;
    background: #fff;
  }
</style>
@endpush
@endsection


