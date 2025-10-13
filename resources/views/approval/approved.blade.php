@extends('layouts.app')
@section('title','Riwayat TTD • Approval')

@section('style')
<style>
  .table thead th{ text-align:center; vertical-align:middle; }
  .table td{ vertical-align: middle; font-size:.9rem }
  .badge-chip{display:inline-flex;align-items:center;gap:.3rem;border-radius:999px;padding:.25rem .55rem;font-weight:800}
  .badge-chip.success{background:#22c55e;color:#fff}
  .btn-icon{
    width:30px;height:30px;border-radius:8px;
    display:inline-flex;align-items:center;justify-content:center;
    color:#fff;border:none;margin:0 2px;
  }
  .btn-teal{background:linear-gradient(180deg,#14b8a6,#0d9488);}
  .btn-indigo{background:linear-gradient(180deg,#6366f1,#4f46e5);}
  .btn-red{background:linear-gradient(180deg,#ef4444,#dc2626);}
  .btn-indigo:hover,.btn-teal:hover,.btn-red:hover{filter:brightness(1.08);}
  .mini-note{font-size:12px;color:#9fb0cd}
  .pdf-frame{width:100%; height:70vh; border:none; border-radius:10px;}
  .qr-thumb{max-width:140px;border-radius:8px;border:1px solid rgba(255,255,255,.18)}
</style>
@endsection

@section('content')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Riwayat Dokumen Disetujui</h3>
    <span class="badge-chip success">Approved</span>
  </div>

  {{-- FILTERS --}}
  <form method="GET" action="{{ route('approval.approved') }}" class="card mb-3">
    <div class="card-body py-3">
      <div class="form-row align-items-end">
        <div class="col-md-2">
          <label class="mb-1 small">Jenis Dokumen</label>
          <select name="doc_type" class="custom-select custom-select-sm">
            @foreach($docOptions as $val => $label)
              <option value="{{ $val }}" {{ request('doc_type','')===(string)$val ? 'selected':'' }}>
                {{ $label }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="mb-1 small">Kategori Rapat</label>
          <select name="kategori" class="custom-select custom-select-sm">
            <option value="">Semua Kategori</option>
            @foreach($daftar_kategori as $kat)
              <option value="{{ $kat->id }}" {{ request('kategori')==$kat->id?'selected':'' }}>
                {{ $kat->nama }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="mb-1 small">Tanggal Rapat</label>
          <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-3">
          <label class="mb-1 small">Cari Judul/Nomor/Tempat</label>
          <input type="text" name="keyword" value="{{ request('keyword') }}" class="form-control form-control-sm" placeholder="Ketik kata kunci ...">
        </div>
        <div class="col-md-2">
          <label class="mb-1 small">Rentang Waktu</label>
          <select name="days" class="custom-select custom-select-sm">
            @foreach($dayOptions as $val => $label)
              <option value="{{ $val }}" {{ (int)request('days', $days)===(int)$val ? 'selected':'' }}>
                {{ $label }}
              </option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="mt-2">
        <button class="btn btn-primary btn-sm">Terapkan Filter</button>
      </div>
    </div>
  </form>

  {{-- DATA --}}
  <div class="card">
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <thead>
          <tr class="text-center">
            <th style="width:54px">#</th>
            <th style="min-width:200px;">Nomor Undangan</th>
            <th style="min-width:260px;">Judul &amp; Kategori</th>
            <th style="min-width:220px;">Waktu &amp; Tempat</th>
            <th style="min-width:120px;">Dokumen</th>
            <th style="min-width:140px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $i => $r)
            @php
              $modalId = 'prev-'.$r->id;
              $qrModal = 'qr-'.$r->id;
            @endphp
            <tr>
              <td class="text-center">
                {{ ($rows->currentPage()-1)*$rows->perPage() + $i + 1 }}
              </td>

              {{-- Nomor (rata kiri) --}}
              <td>
                {{ $r->nomor_undangan ?? '-' }}
              </td>

              {{-- Judul + Kategori --}}
              <td>
                <strong>{{ $r->judul }}</strong>
                <div class="text-muted" style="font-size:12px">{{ $r->nama_kategori ?? '-' }}</div>
              </td>

              {{-- Tanggal + Waktu + Tempat --}}
              <td>
                {{ \Carbon\Carbon::parse($r->tanggal)->format('d M Y') }}
                <div class="text-muted" style="font-size:12px">{{ $r->waktu_mulai }} • {{ $r->tempat }}</div>
              </td>

              {{-- Jenis Dokumen --}}
              <td class="text-center">
                <span class="badge badge-info text-uppercase">{{ $r->doc_type }}</span>
                <div class="mini-note mt-1">
                  Ditandatangani: {{ \Carbon\Carbon::parse($r->signed_at)->format('d M Y H:i') }}
                </div>
              </td>

              {{-- Aksi --}}
              <td class="text-center">
                {{-- Preview PDF (modal iframe) --}}
                @if(!empty($r->preview_url))
                  <button type="button" class="btn-icon btn-indigo" data-toggle="modal" data-target="#{{ $modalId }}" title="Pratinjau Dokumen">
                    <i class="fas fa-file-pdf"></i>
                  </button>
                @endif

                {{-- Lihat QR TTD --}}
                @if(!empty($r->qr_public_url))
                  <button type="button" class="btn-icon btn-teal" data-toggle="modal" data-target="#{{ $qrModal }}" title="QR TTD">
                    <i class="fas fa-qrcode"></i>
                  </button>
                @endif

                {{-- Unduh (buka di tab baru) --}}
                @if(!empty($r->preview_url))
                  <a href="{{ $r->preview_url }}" target="_blank" class="btn-icon btn-red" data-toggle="tooltip" title="Buka di Tab Baru">
                    <i class="fas fa-external-link-alt"></i>
                  </a>
                @endif
              </td>
            </tr>

            {{-- Modal Preview PDF --}}
            @if(!empty($r->preview_url))
            <div class="modal fade" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-hidden="true">
              <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content modal-solid">
                  <div class="modal-header">
                    <h5 class="modal-title">Preview — {{ ucfirst($r->doc_type) }}</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                  </div>
                  <div class="modal-body">
                    <iframe class="pdf-frame" src="{{ $r->preview_url }}"></iframe>
                  </div>
                  <div class="modal-footer">
                    <a href="{{ $r->preview_url }}" target="_blank" class="btn btn-primary btn-sm">
                      <i class="fas fa-external-link-alt mr-1"></i> Buka di Tab Baru
                    </a>
                    <button class="btn btn-outline-light btn-sm" data-dismiss="modal">Tutup</button>
                  </div>
                </div>
              </div>
            </div>
            @endif

            {{-- Modal QR --}}
            @if(!empty($r->qr_public_url))
            <div class="modal fade" id="{{ $qrModal }}" tabindex="-1" role="dialog" aria-hidden="true">
              <div class="modal-dialog" role="document">
                <div class="modal-content modal-solid">
                  <div class="modal-header">
                    <h5 class="modal-title">QR TTD — {{ ucfirst($r->doc_type) }}</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                  </div>
                  <div class="modal-body text-center">
                    <img class="qr-thumb" src="{{ $r->qr_public_url }}" alt="QR TTD">
                    <div class="mini-note mt-2">Order #{{ $r->order_index }} • {{ \Carbon\Carbon::parse($r->signed_at)->format('d M Y H:i') }}</div>
                  </div>
                  <div class="modal-footer">
                    <a href="{{ $r->qr_public_url }}" target="_blank" class="btn btn-primary btn-sm">
                      <i class="fas fa-download mr-1"></i> Buka / Unduh
                    </a>
                    <button class="btn btn-outline-light btn-sm" data-dismiss="modal">Tutup</button>
                  </div>
                </div>
              </div>
            </div>
            @endif

          @empty
            <tr>
              <td colspan="6" class="text-center text-muted p-4">Belum ada dokumen yang Anda setujui pada rentang ini.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- PAGINATION --}}
  <div class="mt-3">
    {{ $rows->links() }}
  </div>
</div>
@endsection
