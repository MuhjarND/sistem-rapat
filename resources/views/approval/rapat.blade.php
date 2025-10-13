@extends('layouts.app')
@section('title','Rapat • Approval')

@section('style')
<style>
  .table thead th{ text-align:center; vertical-align:middle; }
  .table td{ vertical-align: middle; font-size:.9rem }
  .btn-icon{
    width:30px;height:30px;border-radius:8px;
    display:inline-flex;align-items:center;justify-content:center;
    color:#fff;border:none;margin:0 2px;
  }
  .btn-teal{background:linear-gradient(180deg,#14b8a6,#0d9488);}
  .btn-indigo{background:linear-gradient(180deg,#6366f1,#4f46e5);}
  .btn-indigo:hover,.btn-teal:hover{filter:brightness(1.08);}
  .pill{display:inline-flex;align-items:center;justify-content:center;min-width:28px;height:22px;padding:0 8px;border-radius:999px;font-size:12px;font-weight:700;color:#fff;background:#0ea5e9}
  .btn-approve{background:linear-gradient(180deg,#22c55e,#16a34a); color:#fff}
</style>
@endsection

@section('content')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Rapat (Approval)</h3>
  </div>

  {{-- FILTER --}}
  <form method="GET" action="{{ route('approval.rapat') }}" class="card mb-3">
    <div class="card-body py-3">
      <div class="form-row align-items-end">
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
          <label class="mb-1 small">Tanggal</label>
          <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-4">
          <label class="mb-1 small">Cari Judul/Nomor/Tempat</label>
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
            <th style="min-width:220px;">Nomor Undangan</th>
            <th style="min-width:260px;">Judul &amp; Kategori</th>
            <th style="min-width:220px;">Waktu &amp; Tempat</th>
            <th style="width:90px;">Peserta</th>
            <th style="width:140px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($daftar_rapat as $i => $r)
            <tr>
              <td class="text-center">
                {{ ($daftar_rapat->currentPage()-1)*$daftar_rapat->perPage() + $i + 1 }}
              </td>

              {{-- Nomor (rata kiri) --}}
              <td>
                {{ $r->nomor_undangan ?? '-' }}
              </td>

              {{-- Judul + Kategori (disatukan) --}}
              <td>
                <strong>{{ $r->judul }}</strong>
                <div class="text-muted" style="font-size:12px">{{ $r->nama_kategori ?? '-' }}</div>
              </td>

              {{-- Tanggal + waktu + tempat (disatukan) --}}
              <td>
                {{ \Carbon\Carbon::parse($r->tanggal)->format('d M Y') }}
                <div class="text-muted" style="font-size:12px">
                  {{ $r->waktu_mulai }} • {{ $r->tempat }}
                </div>
              </td>

              {{-- Jumlah undangan (bukan hadir) agar sama dengan halaman peserta --}}
              <td class="text-center">
                <span class="pill">{{ $counts[$r->id] ?? 0 }}</span>
              </td>

              <td class="text-center">
                {{-- Detail rapat --}}
                <a href="{{ route('rapat.show', $r->id) }}" class="btn-icon btn-teal" data-toggle="tooltip" title="Detail Rapat">
                  <i class="fas fa-eye"></i>
                </a>

                {{-- Tandatangani (muncul hanya jika ada step saya yang sudah open) --}}
                @if(!empty($nextOpen[$r->id]))
                  <a href="{{ url('/approval/sign/'.$nextOpen[$r->id]->sign_token) }}"
                     class="btn btn-sm btn-approve"
                     data-toggle="tooltip" title="Tandatangani">
                    <i class="fas fa-pen-nib mr-1"></i> Tanda Tangani
                  </a>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-muted p-4">Tidak ada rapat yang terkait dengan Anda.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Pagination --}}
  <div class="mt-3">
    {{ $daftar_rapat->links() }}
  </div>
</div>
@endsection
    