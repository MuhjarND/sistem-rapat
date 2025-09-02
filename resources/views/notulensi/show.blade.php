@extends('layouts.app')
@section('content')
<div class="container">
    <h3>Detail Notulensi</h3>

    {{-- HEADER INSTANSI --}}
    <div class="d-flex justify-content-between mb-3">
      <a href="{{ route('notulensi.cetak', $notulensi->id) }}" class="btn btn-success" target="_blank">Cetak PDF</a>
    </div>

    {{-- INFO RAPAT --}}
    <div class="card mb-3">
      <div class="card-header"><b>Informasi Rapat</b></div>
      <div class="card-body p-0">
        <table class="table mb-0 table-bordered">
            <tbody>
                <tr>
                    <td style="width:28%; background:#2ecc71; font-weight:bold;">Judul Rapat</td>
                    <td>{{ $rapat->judul }}</td>
                </tr>
                <tr>
                    <td style="background:#2ecc71; font-weight:bold;">Jenis Kegiatan</td>
                    <td>{{ $rapat->nama_kategori ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="background:#2ecc71; font-weight:bold;">Hari/Tanggal/Jam</td>
                    <td>{{ \Carbon\Carbon::parse($rapat->tanggal)->translatedFormat('l, d F Y') }} {{ $rapat->waktu_mulai }}</td>
                </tr>
                <tr>
                    <td style="background:#2ecc71; font-weight:bold;">Tempat</td>
                    <td>{{ $rapat->tempat }}</td>
                </tr>
                <tr>
                    <td style="background:#2ecc71; font-weight:bold;">Pimpinan Rapat</td>
                    <td>{{ $rapat->jabatan_pimpinan ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="background:#2ecc71; font-weight:bold;">Agenda</td>
                    <td>{{ $rapat->deskripsi ?: $rapat->judul }}</td>
                </tr>
            </tbody>
        </table>
      </div>
    </div>

    {{-- TABEL DETAIL PEMBAHASAN --}}
    <div class="card mb-3">
      <div class="card-header"><b>Pembahasan & Rangkaian Acara</b></div>
      <div class="card-body p-0">
        <table class="table table-bordered mb-0">
          <thead class="text-center" style="background:#2ecc71;">
            <tr>
              <th style="width:5%;">No</th>
              <th>Hasil Monitoring & Evaluasi / Rangkaian Acara</th>
              <th style="width:25%;">Rekomendasi Tindak Lanjut</th>
              <th style="width:15%;">Penanggung Jawab</th>
              <th style="width:15%;">Tgl. Penyelesaian</th>
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
            <tr><td colspan="5" class="text-center text-muted">Belum ada data pembahasan</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- DOKUMENTASI --}}
    <div class="card mb-3">
      <div class="card-header"><b>Dokumentasi Kegiatan</b></div>
      <div class="card-body">
        <div class="row">
        @forelse($dokumentasi as $dok)
          <div class="col-md-4 mb-3 text-center">
            <img src="{{ asset($dok->file_path) }}" class="img-fluid rounded" alt="dokumentasi">
            @if($dok->caption)
              <small class="d-block mt-1">{{ $dok->caption }}</small>
            @endif
          </div>
        @empty
          <p class="text-muted">Belum ada dokumentasi.</p>
        @endforelse
        </div>
      </div>
    </div>

    {{-- AKSI --}}
    <div class="mt-3">
      <a href="{{ route('notulensi.index') }}" class="btn btn-secondary">Kembali</a>
      <a href="{{ route('notulensi.edit', $notulensi->id) }}" class="btn btn-warning">Edit</a>
    </div>
</div>
@endsection
