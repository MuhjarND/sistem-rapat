@extends('layouts.app')

@section('title','Detail Rapat')

@section('content')
<div class="container">
    {{-- Header + tombol unduh di atas --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
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

    <div class="card p-4">
        <div class="row">
            {{-- Kolom Kiri: Info Rapat + PREVIEW + Peserta --}}
            <div class="col-lg-7">
                <ul class="list-group list-group-flush mb-3">
                    <li class="list-group-item bg-transparent text-light">
                        <strong>Nomor Undangan:</strong> {{ $rapat->nomor_undangan }}
                    </li>
                    <li class="list-group-item bg-transparent text-light">
                        <strong>Judul:</strong> {{ $rapat->judul }}
                    </li>
                    <li class="list-group-item bg-transparent text-light">
                        <strong>Deskripsi:</strong> {{ $rapat->deskripsi }}
                    </li>
                    <li class="list-group-item bg-transparent text-light">
                        <strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($rapat->tanggal)->format('d M Y') }}
                    </li>
                    <li class="list-group-item bg-transparent text-light">
                        <strong>Waktu Mulai:</strong> {{ $rapat->waktu_mulai }}
                    </li>
                    <li class="list-group-item bg-transparent text-light">
                        <strong>Tempat:</strong> {{ $rapat->tempat }}
                    </li>
                </ul>

                {{-- ===== PREVIEW DOKUMEN (Undangan & Laporan Absensi) ===== --}}
                @auth
                @if(in_array(Auth::user()->role, ['admin','notulis','approval','operator']))
                <div class="mb-4">
                    <h5 class="mb-2">Preview Dokumen</h5>

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

                    {{-- Preview Laporan Absensi --}}
                    <div class="mb-3">
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

                <h5 class="mt-4">Peserta Undangan</h5>
                <ul class="mb-0">
                    @foreach($daftar_peserta as $peserta)
                        <li class="text-light">
                            {{ $peserta->name }}
                            @if(!empty($peserta->jabatan))
                                <span class="text-muted">— {{ $peserta->jabatan }}</span>
                            @endif
                            @if(!empty($peserta->email))
                                <span class="text-muted">({{ $peserta->email }})</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Kolom Kanan: QR --}}
            <div class="col-lg-5 border-left">
                @auth
                @if(in_array(Auth::user()->role, ['admin','notulis','operator']))
                    {{-- === QR Peserta (Internal) === --}}
                    <h5 class="mt-2">QR Code Absensi Peserta</h5>
                    <p class="text-muted">Peserta internal (akun sistem) memindai QR ini untuk absen.</p>

                    <div class="p-3 mb-2 rounded d-inline-block" style="background:rgba(255,255,255,.05);">
                        <img src="{{ $qrPesertaImg }}" alt="QR Peserta" style="width:180px;height:auto;border-radius:8px;border:1px solid rgba(226,232,240,.2);background:#fff;">
                    </div>
                    <div class="mt-2 mb-3">
                        <small class="text-muted d-block">Link langsung:</small>
                        <code style="word-break:break-all;">{{ $qrPesertaUrl }}</code>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-light mt-2" onclick="copyToClipboard('{{ $qrPesertaUrl }}')">
                                <i class="fas fa-copy mr-1"></i> Salin tautan
                            </button>
                        </div>
                    </div>

                    {{-- === QR Tamu (Guest) === --}}
                    <h5 class="mt-4">QR Code Absensi Tamu</h5>
                    <p class="text-muted">Tamu eksternal tanpa akun mengisi data & TTD melalui QR ini.</p>

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
                        <div class="mt-2 mb-3">
                            <small class="text-muted d-block">Link langsung:</small>
                            <code style="word-break:break-all;">{{ $qrTamuUrl }}</code>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-light mt-2" onclick="copyToClipboard('{{ $qrTamuUrl }}')">
                                    <i class="fas fa-copy mr-1"></i> Salin tautan
                                </button>
                            </div>
                        </div>
                    @endif
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
    height: 420px; /* tinggi preview */
    border: 0;
    background: #fff;
  }
</style>
@endpush
@endsection
