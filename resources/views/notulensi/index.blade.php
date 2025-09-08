@extends('layouts.app')

@section('title','Notulen Rapat')

@section('style')
<style>
  /* Hilangkan efek hover */
  .table.no-hover tbody tr:hover{ background: transparent !important; }

  /* Header/sel tabel */
  .table thead th{
    text-align:center; vertical-align:middle;
    font-size: 13px; white-space: nowrap;
  }
  .table td{ vertical-align:middle; font-size: 13px; }

  /* Badge jumlah (kecil) */
  .pill-count{
    display:inline-flex; align-items:center; justify-content:center;
    min-width: 28px; height: 24px; padding: 0 8px;
    border-radius: 999px; font-weight: 600; font-size: 12px;
    color:#fff; background: linear-gradient(180deg,#22c55e,#16a34a);
    box-shadow: 0 4px 12px rgba(34,197,94,.25);
  }

  /* Tombol ikon */
  .btn-icon{
    width:30px; height:30px; border-radius:8px;
    display:inline-flex; align-items:center; justify-content:center;
    color:#fff; font-size:13px; border:none; margin:0 2px;
  }
  .btn-teal   { background: linear-gradient(180deg,#14b8a6,#0d9488); }
  .btn-indigo { background: linear-gradient(180deg,#6366f1,#4f46e5); }
  .btn-amber  { background: linear-gradient(180deg,#f59e0b,#d97706); }
  .btn-emerald{ background: linear-gradient(180deg,#10b981,#059669); }
  .btn-rose   { background: linear-gradient(180deg,#ef4444,#dc2626); }

  .btn-teal:hover,.btn-indigo:hover,.btn-amber:hover,.btn-emerald:hover,.btn-rose:hover{
    filter: brightness(1.08);
  }

  /* Lebar kolom agar hemat ruang */
  .col-small   { width: 40px; }
  .col-date    { width: 130px; }
  .col-actions { width: 110px; }
</style>
@endsection

@section('content')
<div class="container">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Notulen Rapat</h3>
  </div>

  @if(session('success'))
    <div class="alert alert-success mt-2">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger mt-2">{{ session('error') }}</div>
  @endif

  {{-- FILTER (sesuai gaya halaman lain) --}}
  <form method="GET" action="{{ route('notulensi.index') }}" class="card mb-3">
    <div class="card-body py-3">
      <div class="form-row align-items-end">
        <div class="col-md-3">
          <label class="mb-1 small">Kategori Rapat</label>
          <select name="kategori" class="custom-select custom-select-sm">
            <option value="">Semua Kategori</option>
            @foreach($daftar_kategori ?? [] as $kat)
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
                 class="form-control form-control-sm" placeholder="Ketik kata kunci ...">
        </div>
        <div class="col-md-2">
          <button class="btn btn-primary btn-block btn-sm">Filter</button>
        </div>
      </div>
    </div>
  </form>

  {{-- ====================== Rapat Belum Memiliki Notulen ====================== --}}
  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <b>Rapat Belum Memiliki Notulen</b>
      <span class="pill-count">{{ $rapat_belum_notulen->count() }}</span>
    </div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0 no-hover">
        <thead>
          <tr class="text-center">
            <th class="col-small">#</th>
            <th>Judul / Nomor</th>
            <th>Kategori</th>
            <th class="col-date">Tgl &amp; Waktu</th>
            <th>Tempat</th>
            <th>Pimpinan</th>
            <th class="col-actions">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rapat_belum_notulen as $i => $r)
            <tr>
              <td class="text-center">{{ $i+1 }}</td>

              <td>
                <strong>{{ $r->judul }}</strong>
                @if(!empty($r->nomor_undangan))
                  <div class="text-muted" style="font-size:11px">{{ $r->nomor_undangan }}</div>
                @endif
              </td>

              <td>{{ $r->nama_kategori ?? '-' }}</td>

              <td class="text-center">
                {{ \Carbon\Carbon::parse($r->tanggal)->format('d M y') }}
                <div class="text-muted" style="font-size:11px">{{ $r->waktu_mulai }}</div>
              </td>

              <td>{{ $r->tempat }}</td>

              <td>
                {{ $r->nama_pimpinan ?? '-' }}
                @if(!empty($r->jabatan_pimpinan))
                  <div class="text-muted" style="font-size:11px">{{ $r->jabatan_pimpinan }}</div>
                @endif
              </td>

              <td class="text-center">
                <a href="{{ route('notulensi.create', $r->id) }}"
                   class="btn-icon btn-emerald"
                   data-toggle="tooltip" title="Buat Notulen">
                  <i class="fas fa-pen"></i>
                </a>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted p-3">Semua rapat sudah memiliki notulen.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- ====================== Rapat Sudah Memiliki Notulen ====================== --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <b>Rapat Sudah Memiliki Notulen</b>
      <span class="pill-count">{{ $rapat_sudah_notulen->count() }}</span>
    </div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0 no-hover">
        <thead>
          <tr class="text-center">
            <th class="col-small">#</th>
            <th>Judul / Nomor</th>
            <th>Kategori</th>
            <th class="col-date">Tgl &amp; Waktu</th>
            <th>Tempat</th>
            <th>Pimpinan</th>
            <th class="col-actions">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rapat_sudah_notulen as $i => $r)
            <tr>
              <td class="text-center">{{ $i+1 }}</td>

              <td>
                <strong>{{ $r->judul }}</strong>
                @if(!empty($r->nomor_undangan))
                  <div class="text-muted" style="font-size:11px">{{ $r->nomor_undangan }}</div>
                @endif
              </td>

              <td>{{ $r->nama_kategori ?? '-' }}</td>

              <td class="text-center">
                {{ \Carbon\Carbon::parse($r->tanggal)->format('d M y') }}
                <div class="text-muted" style="font-size:11px">{{ $r->waktu_mulai }}</div>
              </td>

              <td>{{ $r->tempat }}</td>

              <td>
                {{ $r->nama_pimpinan ?? '-' }}
                @if(!empty($r->jabatan_pimpinan))
                  <div class="text-muted" style="font-size:11px">{{ $r->jabatan_pimpinan }}</div>
                @endif
              </td>

              <td class="text-center">
                <a href="{{ route('notulensi.show', $r->id_notulensi) }}"
                   class="btn-icon btn-teal" data-toggle="tooltip" title="Lihat Notulen">
                  <i class="fas fa-eye"></i>
                </a>
                <a href="{{ route('notulensi.edit', $r->id_notulensi) }}"
                   class="btn-icon btn-amber" data-toggle="tooltip" title="Edit Notulen">
                  <i class="fas fa-edit"></i>
                </a>
                {{-- contoh jika nanti ada cetak PDF --}}
                {{-- <a href="{{ route('notulensi.cetak.pdf', $r->id_notulensi) }}"
                   class="btn-icon btn-indigo" target="_blank" data-toggle="tooltip" title="Cetak PDF">
                  <i class="fas fa-file-pdf"></i>
                </a> --}}
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted p-3">Belum ada notulen yang dibuat.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- PAGINATION (opsional, jika Anda mem-paginate dari controller) --}}
  @if(method_exists($rapat_belum_notulen,'links') || method_exists($rapat_sudah_notulen,'links'))
    <div class="mt-3">
      {{-- contoh jika masing-masing dipaginasi: --}}
      {{-- {{ $rapat_belum_notulen->appends(request()->query())->links() }} --}}
      {{-- {{ $rapat_sudah_notulen->appends(request()->query())->links() }} --}}
    </div>
  @endif

</div>
@endsection
