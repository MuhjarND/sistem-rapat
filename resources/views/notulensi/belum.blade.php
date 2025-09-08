@extends('layouts.app')
@section('title','Notulensi • Belum Dibuat')

@section('style')
<style>
  /* Tombol ikon berwarna (konsisten dengan halaman lain) */
  .btn-icon{
    width:34px;height:34px;border-radius:10px;
    display:inline-flex;align-items:center;justify-content:center;
    border:1px solid rgba(255,255,255,.14); color:#fff;
    transition:.15s ease; padding:0;
  }
  .btn-teal   { background: linear-gradient(180deg,#14b8a6,#0d9488); }
  .btn-amber  { background: linear-gradient(180deg,#f59e0b,#d97706); }
  .btn-indigo { background: linear-gradient(180deg,#6366f1,#4f46e5); }
  .btn-icon:hover{ filter:brightness(1.06); }

  /* Header tabel rapih */
  .table thead th{ text-align:center; vertical-align:middle; }

  /* Hilangkan hover bawaan bila perlu */
  .table.no-hover tbody tr:hover{ background:transparent!important; }
</style>
@endsection

@section('content')
@php
  // Normalisasi nama variabel agar aman terhadap perbedaan nama dari controller
  $daftar = $rapat ?? $rapatBelum ?? $rapat_belum ?? collect();
  $kategoriList = $daftar_kategori ?? $kategori ?? collect();
  $isPaginator = method_exists($daftar, 'total');
  $startNumber = $isPaginator ? ($daftar->currentPage()-1)*$daftar->perPage() + 1 : 1;
@endphp

<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h3 class="mb-0">Rapat Belum Memiliki Notulensi</h3>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  {{-- FILTER --}}
  <form method="GET" action="{{ route('notulensi.belum') }}" class="card mb-3">
    <div class="card-body">
      <div class="form-row align-items-end">
        <div class="col-md-3">
          <label class="mb-1">Kategori Rapat</label>
          <select name="kategori" class="custom-select custom-select-sm">
            <option value="">Semua Kategori</option>
            @foreach($kategoriList as $kat)
              <option value="{{ $kat->id }}" {{ request('kategori')==$kat->id?'selected':'' }}>
                {{ $kat->nama }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="mb-1">Tanggal</label>
          <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-4">
          <label class="mb-1">Cari Judul/Nomor/Tempat</label>
          <input type="text" name="keyword" value="{{ request('keyword') }}" class="form-control form-control-sm" placeholder="Ketik kata kunci ...">
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
      <table class="table table-sm mb-0">
        <thead>
          <tr class="text-center">
            <th style="width:54px">#</th>
            <th style="min-width:240px;">Judul / Nomor</th>
            <th style="min-width:140px;">Kategori</th>
            <th style="min-width:200px;">Tgl &amp; Waktu</th>
            <th style="min-width:160px;">Tempat</th>
            <th style="min-width:170px;">Pimpinan</th>
            <th style="width:140px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse(($isPaginator ? $daftar : collect($daftar)) as $i => $r)
            <tr>
              <td class="text-center">{{ $startNumber + $i }}</td>

              <td>
                <strong>{{ $r->judul }}</strong>
                @if(!empty($r->nomor_undangan))
                  <div class="text-muted" style="font-size:12px">{{ $r->nomor_undangan }}</div>
                @endif
              </td>

              <td>{{ $r->nama_kategori ?? '-' }}</td>

              <td>
                {{ \Carbon\Carbon::parse($r->tanggal)->format('d M Y') }}
                <div class="text-muted" style="font-size:12px">{{ $r->waktu_mulai }}</div>
              </td>

              <td>{{ $r->tempat }}</td>

              <td>
                {{ $r->nama_pimpinan ?? '-' }}
                @if(!empty($r->jabatan_pimpinan))
                  <div class="text-muted" style="font-size:12px">{{ $r->jabatan_pimpinan }}</div>
                @endif
              </td>

              <td class="text-center">
                <a href="{{ route('notulensi.create', $r->id) }}"
                   class="btn-icon btn-amber"
                   data-toggle="tooltip" title="Buat Notulen">
                  <i class="fas fa-plus"></i>
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center text-muted p-4">Semua rapat sudah memiliki notulensi.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- PAGINATION di luar card --}}
  @if($isPaginator)
    <div class="mt-3">
      {{ $daftar->appends(request()->query())->links() }}
    </div>
  @endif
</div>
@endsection
