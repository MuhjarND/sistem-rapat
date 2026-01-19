﻿{{-- resources/views/notulensi/show.blade.php --}}
@extends('layouts.app')

@section('title','Detail Notulensi')

@section('style')
<style>
  .sheet {
    background: linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.02));
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
  }
  .sheet-head{
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
    font-weight: 800;
    letter-spacing:.2px;
    color:#fff;
  }
  .sheet-body{ padding: 0; }

  /* ========== Info table (desktop) ========== */
  .info-table td{
    padding:.75rem 1rem;
    vertical-align: top;
    border-top: 1px solid var(--border);
  }
  .info-key{
    width: 28%;
    font-weight: 700;
    color:#e6eefc;
    background: rgba(79,70,229,.12);
    border-right: 1px solid var(--border);
    white-space: nowrap;
  }

  .action-bar{ display:flex; gap:.5rem; flex-wrap:wrap; }
  .action-bar .btn{
    border-radius: 12px;
    font-weight: 700;
  }
  .btn-indigo{
    background: linear-gradient(180deg,#6366f1,#4f46e5);
    border-color: transparent;
    color:#fff;
  }
  .btn-indigo:hover{ filter:brightness(1.05); }
  .btn-teal{
    background: linear-gradient(180deg,#14b8a6,#0d9488);
    border-color: transparent;
    color:#fff;
  }
  .btn-teal:hover{ filter:brightness(1.05); }

  .gallery img{
    width:100%;
    height: 220px;
    object-fit: cover;
    border:1px solid var(--border);
    box-shadow: var(--shadow);
    border-radius: 10px;
  }
  .table.tight td, .table.tight th{ padding:.65rem .8rem; }

  .assignee-chip{
    display:inline-flex;align-items:center;gap:.4rem;
    background:rgba(79,70,229,.18);
    border:1px solid rgba(79,70,229,.35);
    color:#fff;border-radius:999px;padding:.15rem .55rem;margin:.12rem .18rem .12rem 0;
    font-size:.82rem;font-weight:700;white-space:nowrap;
  }
  .muted{color:var(--muted)}

  /* ========== Responsive: Info table, Discussion table to cards ========== */
  @media (max-width: 575.98px){
    /* Action buttons full width if needed */
    .action-bar .btn{ flex:1 1 100%; }

    /* Info table -> stacked */
    .info-table{ width:100%; }
    .info-table tr{ display:block; border-bottom:1px solid var(--border); }
    .info-table td{ display:block; width:100%; border:0; }
    .info-key{
      width:auto; display:block; border-right:0;
      background:transparent; padding-bottom:.35rem; color:#9fb0cd; font-size:.8rem; text-transform:uppercase;
    }

    /* Discussion table -> cards */
    .table.tight thead{ display:none; }
    .table.tight tbody tr{
      display:block; margin:10px 12px; border:1px solid var(--border);
      border-radius:12px; overflow:hidden; background:rgba(255,255,255,.02);
    }
    .table.tight tbody td{
      display:block; width:100%; border:0 !important; border-bottom:1px solid var(--border) !important;
      padding:.7rem .85rem;
    }
    .table.tight tbody td:last-child{ border-bottom:0 !important; }
    .table.tight tbody td[data-label]::before{
      content: attr(data-label);
      display:block; font-size:.72rem; font-weight:800; letter-spacing:.2px;
      color:#9fb0cd; text-transform:uppercase; margin-bottom:6px;
    }

    /* Gallery auto height */
    .gallery img{ height:auto; }
  }
</style>
@endsection

@section('content')
@php
  // ambil data approval 1 dan jumlah hadir
  $approval1 = \DB::table('users')
      ->where('id', $rapat->approval1_user_id)
      ->select('name','jabatan','unit')
      ->first();
  $approval1_jabatan = $rapat->approval1_jabatan_manual ?: ($approval1->jabatan ?? null);

  // jumlah peserta yang mengikuti (hadir) rapat -> dihitung dari absensi
  $pesertaHadirCount = \DB::table('absensi')
      ->where('id_rapat', $rapat->id)
      ->count();
@endphp

<div class="container">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Detail Notulensi</h3>

    <div class="action-bar">
      <a href="{{ route('notulensi.cetak', $notulensi->id) }}" target="_blank" class="btn btn-teal">
        <i class="fas fa-print mr-1"></i> Cetak PDF
      </a>
      <a href="{{ route('notulensi.edit', $notulensi->id) }}" class="btn btn-warning text-dark">
        <i class="fas fa-edit mr-1"></i> Edit
      </a>
      <a href="{{ route('notulensi.index') }}" class="btn btn-outline-light">
        <i class="fas fa-arrow-left mr-1"></i> Kembali
      </a>
    </div>
  </div>

  {{-- INFORMASI RAPAT --}}
  <div class="sheet mb-3">
    <div class="sheet-head">Informasi Rapat</div>
    <div class="sheet-body">
      <table class="table mb-0 info-table">
        <tbody>
          <tr>
            <td class="info-key">Judul Rapat</td>
            <td>{{ $rapat->judul }}</td>
          </tr>
          <tr>
            <td class="info-key">Jenis Kegiatan</td>
            <td>{{ $rapat->nama_kategori ?? '-' }}</td>
          </tr>
          <tr>
            <td class="info-key">Hari/Tanggal/Jam</td>
            <td>{{ \Carbon\Carbon::parse($rapat->tanggal)->translatedFormat('l, d F Y') }} {{ \App\Helpers\TimeHelper::short($rapat->waktu_mulai) }}</td>
          </tr>
          <tr>
            <td class="info-key">Tempat</td>
            <td>{{ $rapat->tempat }}</td>
          </tr>
          <tr>
            <td class="info-key">Pemimpin Rapat</td>
            <td>
              @if($approval1)
                <strong>{{ $approval1->name }}</strong>
                <span class="muted">
                  - {{ $approval1_jabatan ?: '-' }}{{ $approval1->unit ? ' - '.$approval1->unit : '' }}
                </span>
              @else
                <span class="muted">-</span>
              @endif
            </td>
          </tr>
          <tr>
            <td class="info-key">Jumlah Peserta Hadir</td>
            <td><strong>{{ $pesertaHadirCount }}</strong> orang</td>
          </tr>
          <tr>
            <td class="info-key">Agenda</td>
            <td>{{ $rapat->deskripsi ?: $rapat->judul }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  @if(!empty($notulensi->file_path))
    <div class="sheet mb-3">
      <div class="sheet-head">File Notulensi</div>
      <div class="p-3">
        <a href="{{ asset($notulensi->file_path) }}" target="_blank" class="btn btn-teal btn-sm">
          <i class="fas fa-file-pdf mr-1"></i> Buka File Notulensi
        </a>
        <div class="text-muted mt-2">
          {{ $notulensi->file_name ?: basename($notulensi->file_path) }}
        </div>
      </div>
    </div>
  @endif

  @if(($notulensi->template ?? 'a') === 'b')
    <div class="sheet mb-3">
      <div class="sheet-head">Template B - Ringkasan</div>
      <div class="p-3">
        <div class="mb-2">
          <strong>Agenda Rapat</strong><br>
          {!! nl2br(e($notulensi->agenda ?: ($rapat->deskripsi ?: $rapat->judul))) !!}
        </div>
        <div class="mb-2">
          <strong>Susunan Agenda</strong><br>
          {!! nl2br(e($notulensi->susunan_agenda ?: '-')) !!}
        </div>
        <div>
          <strong>Hasil Rapat</strong><br>
          @if(!empty($notulensi->hasil_rapat))
            {!! $notulensi->hasil_rapat !!}
          @else
            -
          @endif
        </div>
      </div>
    </div>
  @endif

  {{-- TABEL PEMBAHASAN --}}
  <div class="sheet mb-3">
    <div class="sheet-head">Pembahasan &amp; Rangkaian Acara</div>
    <div class="sheet-body">
      <div class="table-responsive">
        <table class="table table-bordered table-sm mb-0 tight">
          <thead class="text-center">
            <tr>
              <th style="width:5%;">No</th>
              <th>Hasil Monitoring &amp; Evaluasi / Rangkaian Acara</th>
              <th style="width:26%;">Rekomendasi Tindak Lanjut</th>
              <th style="width:24%;">Penanggung Jawab</th>
              <th style="width:16%;">Tgl. Penyelesaian</th>
            </tr>
          </thead>
          <tbody>
            @forelse($detail as $row)
              @php
                // daftar peserta yang ditugaskan untuk baris detail ini
                $assignees = \DB::table('notulensi_tugas')
                    ->join('users','users.id','=','notulensi_tugas.user_id')
                    ->where('id_notulensi_detail', $row->id)
                    ->select('users.name','users.jabatan','users.unit')
                    ->get();
              @endphp
              <tr>
                <td class="text-center" data-label="No">{{ $row->urut }}</td>

                <td data-label="Hasil / Rangkaian">{!! $row->hasil_pembahasan !!}</td>

                <td data-label="Rekomendasi">{!! $row->rekomendasi !!}</td>

                <td data-label="Penanggung Jawab">
                  @if($assignees->count())
                    @foreach($assignees as $a)
                      <span class="assignee-chip">
                        {{ $a->name }}
                        @if($a->jabatan) <span class="muted">- {{ $a->jabatan }}</span>@endif
                        @if($a->unit) <span class="muted">- {{ $a->unit }}</span>@endif
                      </span><br>
                    @endforeach
                  @else
                    {{ $row->penanggung_jawab ?: '-' }}
                  @endif
                </td>

                <td data-label="Tgl. Penyelesaian">
                  {{ $row->tgl_penyelesaian ? \Carbon\Carbon::parse($row->tgl_penyelesaian)->format('d/m/Y') : '-' }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center text-muted p-3">Belum ada data pembahasan.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- DOKUMENTASI --}}
  <div class="sheet mb-3">
    <div class="sheet-head">Dokumentasi Kegiatan</div>
    <div class="p-3">
      <div class="row">
        @forelse($dokumentasi as $dok)
          <div class="col-lg-4 col-md-6 mb-3">
            <div class="gallery">
              <img src="{{ asset($dok->file_path) }}" class="img-fluid" alt="dokumentasi">
            </div>
            @if($dok->caption)
              <small class="d-block mt-2 text-muted">{{ $dok->caption }}</small>
            @endif
          </div>
        @empty
          <div class="col-12">
            <p class="text-muted mb-0">Belum ada dokumentasi.</p>
          </div>
        @endforelse
      </div>
    </div>
  </div>

  {{-- AKSI BAWAH --}}
  <div class="d-flex justify-content-end flex-wrap" style="gap:.5rem">
    <a href="{{ route('notulensi.index') }}" class="btn btn-outline-light">
      <i class="fas fa-arrow-left mr-1"></i> Kembali
    </a>
    <a href="{{ route('notulensi.edit', $notulensi->id) }}" class="btn btn-warning text-dark">
      <i class="fas fa-edit mr-1"></i> Edit
    </a>
    <a href="{{ route('notulensi.cetak', $notulensi->id) }}" target="_blank" class="btn btn-teal">
      <i class="fas fa-print mr-1"></i> Cetak PDF
    </a>
  </div>

</div>
@endsection


