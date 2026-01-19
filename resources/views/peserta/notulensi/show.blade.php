@extends('layouts.app')
@section('title','Notulensi Rapat')

@push('style')
<style>
  /* Header info */
  .meta-chip{
    display:inline-flex;align-items:center;gap:8px;
    padding:.35rem .6rem;border-radius:999px;
    background:rgba(255,255,255,.06);border:1px solid rgba(226,232,240,.15);
    font-weight:600;font-size:.85rem;color:var(--text)
  }
  .meta-chip i{opacity:.9}

  /* Section title */
  .section-title{
    font-weight:800;color:#fff;letter-spacing:.2px;margin:0
  }

  /* Timeline / Step card */
  .step{
    position:relative;border:1px solid var(--border);
    background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.02));
    border-radius:14px;box-shadow:var(--shadow);
    padding:16px 16px 14px 16px
  }
  .step + .step{ margin-top:12px }

  .step-num{
    width:34px;height:34px;border-radius:999px;flex:0 0 34px;
    display:flex;align-items:center;justify-content:center;
    background:linear-gradient(180deg, rgba(79,70,229,.35), rgba(14,165,233,.25));
    border:1px solid rgba(99,102,241,.25);
    color:#fff;font-weight:800
  }

  .step-head{display:flex;align-items:center;gap:12px;margin-bottom:8px}
  .step-title{font-weight:800;color:#fff;margin:0;font-size:1.02rem}

  .content-box{
    color:var(--text);line-height:1.55;font-size:.95rem
  }
  .content-box :is(h1,h2,h3,h4){font-size:1rem;margin:.2rem 0 .35rem 0}
  .content-box :is(ul,ol){margin:.35rem 0 .5rem 1.1rem}
  .content-box p{margin-bottom:.35rem}

  .rekom-box{
    border-left:3px solid var(--info);
    background:rgba(14,165,233,.08);
    padding:.6rem .75rem;border-radius:8px;margin-top:.5rem
  }

  .mini-meta{
    display:flex;flex-wrap:wrap;gap:8px;margin-top:.6rem
  }
  .mini-chip{
    display:inline-flex;align-items:center;gap:6px;
    padding:.28rem .55rem;border-radius:999px;
    background:rgba(255,255,255,.06);
    border:1px solid rgba(226,232,240,.15);
    font-size:.82rem;color:var(--text);font-weight:600
  }
  .mini-chip i{opacity:.9}

  .empty{
    border:1px dashed var(--border);
    border-radius:12px;padding:14px;text-align:center;color:var(--muted)
  }
</style>
@endpush

@section('content')
<div class="card mb-3">
  <div class="card-header d-flex align-items-center">
    <strong class="section-title">Notulensi</strong>
    <div class="ml-auto">
      <a href="{{ route('peserta.rapat') }}" class="btn btn-sm btn-outline-light">
        <i class="fas fa-list mr-1"></i> Daftar Rapat
      </a>
      <a href="{{ route('peserta.dashboard') }}" class="btn btn-sm btn-outline-light ml-2">
        <i class="fas fa-home mr-1"></i> Dashboard
      </a>
    </div>
  </div>

  <div class="card-body">
    {{-- Judul + ringkasan rapat --}}
    <h4 class="mb-1" style="font-weight:800;color:#fff">{{ $rapat->judul }}</h4>
    <div class="mb-3" style="display:flex;gap:8px;flex-wrap:wrap">
      <span class="meta-chip"><i class="far fa-calendar"></i>{{ \Carbon\Carbon::parse($rapat->tanggal)->isoFormat('dddd, D MMMM Y') }}</span>
      <span class="meta-chip"><i class="far fa-clock"></i>{{ \App\Helpers\TimeHelper::short($rapat->waktu_mulai) }} WIT</span>
      <span class="meta-chip"><i class="fas fa-map-marker-alt"></i>{{ $rapat->tempat }}</span>
      @if(!empty($rapat->nama_kategori))
        <span class="meta-chip"><i class="fas fa-layer-group"></i>{{ $rapat->nama_kategori }}</span>
      @endif
      @if(!empty($rapat->nama_pimpinan))
        <span class="meta-chip"><i class="fas fa-user-tie"></i>{{ $rapat->nama_pimpinan }}</span>
      @endif
    </div>

    @if(!empty($notulensi->file_path))
      <div class="mb-3">
        <a href="{{ asset($notulensi->file_path) }}" target="_blank" class="btn btn-sm btn-outline-light">
          <i class="fas fa-file-pdf mr-1"></i> Buka File Notulensi
        </a>
        <div class="text-muted mt-1">{{ $notulensi->file_name ?: basename($notulensi->file_path) }}</div>
      </div>
    @endif

    @if(($notulensi->template ?? 'a') === 'b')
      <div class="mb-3">
        <div class="meta-chip mb-2"><i class="fas fa-list"></i>Agenda Rapat</div>
        <div class="content-box mb-2">{!! nl2br(e($notulensi->agenda ?: ($rapat->deskripsi ?: $rapat->judul))) !!}</div>

        <div class="meta-chip mb-2"><i class="fas fa-stream"></i>Susunan Agenda</div>
        <div class="content-box mb-2">{!! nl2br(e($notulensi->susunan_agenda ?: '-')) !!}</div>

        <div class="meta-chip mb-2"><i class="fas fa-clipboard-check"></i>Hasil Rapat</div>
        <div class="content-box">
          @if(!empty($notulensi->hasil_rapat))
            {!! $notulensi->hasil_rapat !!}
          @else
            -
          @endif
        </div>
      </div>
    @endif

    {{-- Daftar poin notulensi --}}
    @forelse($detail as $i => $row)
      <div class="step">
        <div class="step-head">
          <div class="step-num">{{ $row->urut ?? ($i+1) }}</div>
          <h6 class="step-title">Hasil Pembahasan</h6>
        </div>

        <div class="content-box">
          {!! $row->hasil_pembahasan !!}
        </div>

        @if(!empty($row->rekomendasi))
          <div class="rekom-box content-box">
            <div class="d-flex align-items-center mb-1" style="gap:8px;color:#cfe9fb">
              <i class="fas fa-lightbulb"></i> <b>Rekomendasi</b>
            </div>
            {!! $row->rekomendasi !!}
          </div>
        @endif

        <div class="mini-meta">
          <span class="mini-chip">
            <i class="fas fa-user-check"></i>
            PJ: {{ $row->penanggung_jawab ?: '-' }}
          </span>
          <span class="mini-chip">
            <i class="far fa-calendar-check"></i>
            Target: {{ $row->tgl_penyelesaian ? \Carbon\Carbon::parse($row->tgl_penyelesaian)->isoFormat('D MMM Y') : '-' }}
          </span>
        </div>
      </div>
    @empty
      <div class="empty">Belum ada detail notulensi.</div>
    @endforelse
  </div>
</div>
@endsection


