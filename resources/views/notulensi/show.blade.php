{{-- resources/views/notulensi/show.blade.php --}}
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
  }
  .table.tight td, .table.tight th{ padding:.65rem .8rem; }
</style>
@endsection

@section('content')
<div class="container">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Detail Notulensi</h3>

    <div class="action-bar">
      <a href="{{ route('notulensi.cetak', $notulensi->id) }}"
         target="_blank"
         class="btn btn-teal">
        <i class="fas fa-print mr-1"></i> Cetak PDF
      </a>
      <a href="{{ route('notulensi.edit', $notulensi->id) }}"
         class="btn btn-warning text-dark">
        <i class="fas fa-edit mr-1"></i> Edit
      </a>
      <a href="{{ route('notulensi.index') }}"
         class="btn btn-outline-light">
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
            <td>{{ \Carbon\Carbon::parse($rapat->tanggal)->translatedFormat('l, d F Y') }} {{ $rapat->waktu_mulai }}</td>
          </tr>
          <tr>
            <td class="info-key">Tempat</td>
            <td>{{ $rapat->tempat }}</td>
          </tr>
          <tr>
            <td class="info-key">Pimpinan Rapat</td>
            <td>{{ $rapat->jabatan_pimpinan ?? '-' }}</td>
          </tr>
          <tr>
            <td class="info-key">Agenda</td>
            <td>{{ $rapat->deskripsi ?: $rapat->judul }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  {{-- TABEL PEMBAHASAN --}}
  <div class="sheet mb-3">
    <div class="sheet-head">Pembahasan &amp; Rangkaian Acara</div>
    <div class="sheet-body">
      <table class="table table-bordered table-sm mb-0 tight">
        <thead class="text-center">
          <tr>
            <th style="width:5%;">No</th>
            <th>Hasil Monitoring &amp; Evaluasi / Rangkaian Acara</th>
            <th style="width:26%;">Rekomendasi Tindak Lanjut</th>
            <th style="width:16%;">Penanggung Jawab</th>
            <th style="width:16%;">Tgl. Penyelesaian</th>
          </tr>
        </thead>
        <tbody>
          @forelse($detail as $row)
            <tr>
              <td class="text-center">{{ $row->urut }}</td>
              <td>{!! $row->hasil_pembahasan !!}</td>
              <td>{!! $row->rekomendasi !!}</td>
              <td>{{ $row->penanggung_jawab ?? '-' }}</td>
              <td>{{ $row->tgl_penyelesaian ? \Carbon\Carbon::parse($row->tgl_penyelesaian)->format('d/m/Y') : '-' }}</td>
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

  {{-- DOKUMENTASI --}}
  <div class="sheet mb-3">
    <div class="sheet-head">Dokumentasi Kegiatan</div>
    <div class="p-3">
      <div class="row">
        @forelse($dokumentasi as $dok)
          <div class="col-lg-4 col-md-6 mb-3">
            <div class="gallery">
              <img src="{{ asset($dok->file_path) }}" class="img-fluid rounded" alt="dokumentasi">
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

  {{-- AKSI BAWAH (duplikat untuk kenyamanan) --}}
  <div class="d-flex justify-content-end gap-2">
    <a href="{{ route('notulensi.index') }}" class="btn btn-outline-light mr-2">
      <i class="fas fa-arrow-left mr-1"></i> Kembali
    </a>
    <a href="{{ route('notulensi.edit', $notulensi->id) }}" class="btn btn-warning text-dark mr-2">
      <i class="fas fa-edit mr-1"></i> Edit
    </a>
    <a href="{{ route('notulensi.cetak', $notulensi->id) }}" target="_blank" class="btn btn-teal">
      <i class="fas fa-print mr-1"></i> Cetak PDF
    </a>
  </div>

</div>
@endsection
