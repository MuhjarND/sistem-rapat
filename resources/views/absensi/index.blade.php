@extends('layouts.app')

@section('title','Absensi Rapat')

@section('style')
<style>
  /* Hilangkan efek hover */
  .table.no-hover tbody tr:hover { background: transparent !important; }

  /* Badge jumlah undangan */
  .pill-count{
    display:inline-flex; align-items:center; justify-content:center;
    min-width: 28px; height: 24px; padding: 0 8px;
    border-radius: 999px;
    font-weight: 600; font-size: 12px;
    color:#fff;
    background: linear-gradient(180deg, #22c55e, #16a34a);
    box-shadow: 0 4px 12px rgba(34,197,94,.25);
  }

  /* Badge jumlah hadir */
  .pill-hadir{
    display:inline-flex; align-items:center; justify-content:center;
    min-width: 28px; height: 24px; padding: 0 8px;
    border-radius: 999px;
    font-weight: 600; font-size: 12px;
    color:#fff;
    background: linear-gradient(180deg, #3b82f6, #2563eb);
    box-shadow: 0 4px 12px rgba(37,99,235,.25);
  }

  /* Tombol ikon */
  .btn-icon{
    width:30px; height:30px; border-radius:8px;
    display:inline-flex; align-items:center; justify-content:center;
    border: none;
    color:#fff; font-size:13px;
    margin: 0 1px;
  }
  .btn-teal   { background: linear-gradient(180deg,#14b8a6,#0d9488); }
  .btn-indigo { background: linear-gradient(180deg,#6366f1,#4f46e5); }
  .btn-teal:hover, .btn-indigo:hover { filter: brightness(1.08); }

  /* Header tabel */
  .table thead th{
    text-align:center; vertical-align:middle;
    font-size: 13px;
    white-space: nowrap;
  }
  .table td { font-size: 13px; vertical-align: middle; }
</style>
@endsection

@section('content')
<div class="container">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Daftar Absensi Rapat</h3>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  {{-- FILTER --}}
  <form method="GET" action="{{ route('absensi.index') }}" class="card mb-3">
    <div class="card-body py-3">
      <div class="form-row align-items-end">
        <div class="col-md-3">
          <label class="mb-1 small">Kategori Rapat</label>
          <select name="kategori" class="custom-select custom-select-sm">
            <option value="">Semua Kategori</option>
            @foreach($daftar_kategori as $kat)
              <option value="{{ $kat->id }}" {{ request('kategori')==$kat->id ? 'selected':'' }}>
                {{ $kat->nama }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="mb-1 small">Tanggal</label>
          <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-4">
          <label class="mb-1 small">Cari Judul/Nomor/Tempat</label>
          <input type="text" name="keyword" value="{{ request('keyword') }}"
                 class="form-control form-control-sm"
                 placeholder="Ketik kata kunci ...">
        </div>
        <div class="col-md-2">
          <button class="btn btn-primary btn-block btn-sm">Filter</button>
        </div>
      </div>
    </div>
  </form>

  {{-- DATA --}}
  <div class="card">
    <div class="card-body p-0">
      <table class="table table-sm mb-0 no-hover">
        <thead>
          <tr class="text-center">
            <th style="width:40px">#</th>
            <th style="min-width:160px;">Nomor Undangan</th>
            <th>Judul &amp; Kategori</th>
            <th style="min-width:230px;">Tanggal, Waktu &amp; Tempat</th>
            <th style="width:80px;">Undangan</th>
            <th style="width:80px;">Hadir</th>
            <th style="width:90px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($daftar_rapat as $index => $rapat)
            @php
              // Hitung jumlah hadir dari tabel absensi
              $jumlahHadir = \DB::table('absensi')
                ->where('id_rapat', $rapat->id)
                ->where('status', 'hadir')
                ->count();
            @endphp
            <tr>
              <td class="text-center">
                {{ ($daftar_rapat->currentPage()-1) * $daftar_rapat->perPage() + $index + 1 }}
              </td>

              {{-- Nomor Undangan (rata kiri) --}}
              <td>{{ $rapat->nomor_undangan ?? '—' }}</td>

              {{-- Judul + Kategori --}}
              <td>
                <strong>{{ $rapat->judul }}</strong>
                <div class="text-muted" style="font-size:12px">{{ $rapat->nama_kategori ?? '-' }}</div>
              </td>

              {{-- Tanggal + Waktu + Tempat (disatukan) --}}
              <td>
                {{ \Carbon\Carbon::parse($rapat->tanggal)->translatedFormat('l, d F Y') }}
                <div class="text-muted" style="font-size:11px">{{ $rapat->waktu_mulai }}</div>
                <div class="text-muted" style="font-size:12px"><i class="fas fa-map-marker-alt mr-1"></i>{{ $rapat->tempat }}</div>
              </td>

              {{-- Undangan (jumlah peserta yang diundang) --}}
              <td class="text-center">
                <span class="pill-count">{{ $rapat->jumlah_peserta ?? 0 }}</span>
              </td>

              {{-- Hadir (jumlah peserta hadir) --}}
              <td class="text-center">
                <span class="pill-hadir">{{ $jumlahHadir }}</span>
              </td>

              {{-- Aksi --}}
              <td class="text-center">
                <a href="{{ route('rapat.show', $rapat->id) }}"
                   class="btn-icon btn-teal"
                   data-toggle="tooltip" title="Detail Rapat">
                  <i class="fas fa-eye"></i>
                </a>
                <a href="{{ route('absensi.export.pdf', $rapat->id) }}"
                   target="_blank"
                   class="btn-icon btn-indigo"
                   data-toggle="tooltip" title="Unduh PDF Absensi">
                  <i class="fas fa-file-download"></i>
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center text-muted p-4">
                Belum ada rapat untuk absensi.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- PAGINATION (di luar card) --}}
  <div class="mt-3">
    {{ $daftar_rapat->appends(request()->query())->links() }}
  </div>

</div>
@endsection
