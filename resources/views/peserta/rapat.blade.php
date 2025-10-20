@extends('layouts.app')

@section('title','Rapat Saya')

@push('style')
<style>
  /* Mobile cards */
  .m-list{padding:10px}
  .m-card{
    background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.02));
    border:1px solid rgba(226,232,240,.15);
    border-radius:12px;
    padding:12px 12px;
    margin-bottom:10px;
    color:var(--text);
  }
  .m-title{font-weight:800; line-height:1.25; font-size:1.02rem; color:#fff}
  .m-sub{font-size:.82rem; color:#9fb0cd}
  .m-meta{display:flex; flex-wrap:wrap; gap:8px; font-size:.8rem; color:#c7d2fe}
  .mchip{
    display:inline-flex; align-items:center; gap:.35rem;
    padding:.18rem .5rem; border-radius:999px; font-size:.72rem; font-weight:800;
    background:rgba(79,70,229,.25); border:1px solid rgba(79,70,229,.35); color:#fff;
  }
  .mchip.info{ background:rgba(14,165,233,.25); border-color:rgba(14,165,233,.35) }
  .mchip.warn{ background:rgba(245,158,11,.2); border-color:rgba(245,158,11,.35) }
  .mchip.ok{ background:rgba(34,197,94,.22); border-color:rgba(34,197,94,.35) }
  .btn-pill{
    display:inline-flex; align-items:center; gap:6px;
    border:1px solid rgba(255,255,255,.18);
    padding:.38rem .65rem; border-radius:10px; font-size:.86rem; color:#fff; text-decoration:none;
    background:rgba(255,255,255,.06);
  }
  .btn-pill.primary{ background:linear-gradient(180deg,var(--primary),var(--primary-700)); border-color:transparent; }
</style>
@endpush

@section('content')
<div class="card mb-4">
  <div class="card-header d-flex align-items-center">
      <span><i class="fas fa-calendar-alt mr-2"></i> Daftar Rapat</span>
      <a href="{{ route('peserta.dashboard') }}" class="ml-auto btn btn-sm btn-outline-light">
          <i class="fas fa-home mr-1"></i> Dashboard
      </a>
  </div>

  <div class="card-body">
      {{-- Filter --}}
      <form method="get" class="mb-3">
          <div class="form-row">
              <div class="col-md-3 mb-2">
                  <label class="mb-1">Jenis</label>
                  <select name="jenis" class="custom-select">
                      <option value="upcoming" {{ ($filter['jenis']??'')==='upcoming'?'selected':'' }}>Akan Datang</option>
                      <option value="past"     {{ ($filter['jenis']??'')==='past'?'selected':'' }}>Sudah Berlalu</option>
                      <option value="all"      {{ ($filter['jenis']??'')==='all'?'selected':'' }}>Semua</option>
                  </select>
              </div>
              <div class="col-md-3 mb-2">
                  <label class="mb-1">Kata Kunci</label>
                  <input type="text" name="q" value="{{ $filter['q']??'' }}" class="form-control" placeholder="Judul / Nomor / Tempat">
              </div>
              <div class="col-md-2 mb-2">
                  <label class="mb-1">Dari Tanggal</label>
                  <input type="date" name="from" value="{{ $filter['from']??'' }}" class="form-control">
              </div>
              <div class="col-md-2 mb-2">
                  <label class="mb-1">Sampai</label>
                  <input type="date" name="to" value="{{ $filter['to']??'' }}" class="form-control">
              </div>
              <div class="col-md-2 mb-2 d-flex align-items-end">
                  <button class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i> Filter</button>
              </div>
          </div>
      </form>

      {{-- ===== Desktop Table ===== --}}
      <div class="table-responsive d-none d-md-block">
          <table class="table table-hover mb-0">
              <thead>
              <tr>
                  <th style="min-width:240px;">Judul</th>
                  <th>Nomor</th>
                  <th class="text-center" style="min-width:160px;">Tanggal & Waktu</th>
                  <th>Tempat</th>
                  <th class="text-center">Absensi</th>
                  <th class="text-center">Notulensi</th>
                  <th class="text-center" style="width:120px;">Aksi</th>
              </tr>
              </thead>
              <tbody>
              @forelse($rapat as $r)
                  <tr>
                      <td>
                          <a class="text-light" href="{{ route('peserta.rapat.show', $r->id) }}">{{ $r->judul }}</a>
                          @if(!empty($r->nama_kategori))
                              <div class="text-muted small">{{ $r->nama_kategori }}</div>
                          @endif
                      </td>
                      <td>{{ $r->nomor_undangan ?? '—' }}</td>

                      {{-- Gabung tanggal & waktu --}}
                      <td class="text-center">
                          {{ \Carbon\Carbon::parse($r->tanggal)->isoFormat('D/MM/Y') }}
                          <div class="text-muted small">{{ $r->waktu_mulai }} WIT</div>
                      </td>

                      <td>{{ $r->tempat }}</td>

                      {{-- Absensi --}}
                      <td class="text-center">
                          @php $s = $r->status_absensi; @endphp
                          @if($s === 'hadir')
                              <span class="badge badge-success">HADIR</span>
                          @elseif($s === 'izin')
                              <span class="badge badge-warning">IZIN</span>
                          @elseif($s === 'alfa')
                              <span class="badge badge-danger">ALFA</span>
                          @else
                              @if(!empty($r->token_qr))
                                  <a href="{{ route('absensi.scan', $r->token_qr) }}"
                                     class="btn btn-sm btn-outline-light">
                                      Konfirmasi
                                  </a>
                              @else
                                  <a href="{{ route('peserta.absensi', $r->id) }}"
                                     class="btn btn-sm btn-outline-light">
                                      Konfirmasi
                                  </a>
                              @endif
                          @endif
                      </td>

                      {{-- Notulensi --}}
                      <td class="text-center">
                          @if(!empty($r->id_notulensi))
                              <a href="{{ route('peserta.notulensi.show', $r->id) }}" class="btn btn-sm btn-info">Lihat</a>
                          @else
                              <span class="badge badge-secondary">Belum ada</span>
                          @endif
                      </td>

                      {{-- Aksi --}}
                      <td class="text-center">
                          <a href="{{ route('peserta.rapat.show', $r->id) }}" class="btn btn-sm btn-outline-light">
                              Detail
                          </a>
                      </td>
                  </tr>
              @empty
                  <tr><td colspan="7" class="text-center text-muted">Tidak ada data.</td></tr>
              @endforelse
              </tbody>
          </table>
      </div>

      {{-- ===== Mobile Cards ===== --}}
      <div class="d-block d-md-none m-list">
        @forelse($rapat as $r)
          <div class="m-card">
            <div class="m-title">{{ $r->judul }}</div>
            @if(!empty($r->nama_kategori))
              <div class="m-sub">{{ $r->nama_kategori }}</div>
            @endif>

            <div class="m-meta mt-1">
              <span class="mchip info">
                {{ \Carbon\Carbon::parse($r->tanggal)->isoFormat('D/MM/Y') }} • {{ $r->waktu_mulai }} WIT
              </span>
              <span class="mchip">
                {{ $r->tempat }}
              </span>
              @if($r->nomor_undangan)
                <span class="mchip">No: {{ $r->nomor_undangan }}</span>
              @endif
            </div>

            <div class="mt-2 d-flex flex-wrap" style="gap:8px">
              {{-- Absensi --}}
              @php $s = $r->status_absensi; @endphp
              @if($s === 'hadir')
                <span class="mchip ok">HADIR</span>
              @elseif($s === 'izin')
                <span class="mchip warn">IZIN</span>
              @elseif($s === 'alfa')
                <span class="mchip warn">ALFA</span>
              @else
                @if(!empty($r->token_qr))
                  <a href="{{ route('absensi.scan', $r->token_qr) }}" class="btn-pill">
                    <i class="fas fa-check-circle"></i> Konfirmasi Absensi
                  </a>
                @else
                  <a href="{{ route('peserta.absensi', $r->id) }}" class="btn-pill">
                    <i class="fas fa-check-circle"></i> Konfirmasi Absensi
                  </a>
                @endif
              @endif

              {{-- Notulensi --}}
              @if(!empty($r->id_notulensi))
                <a href="{{ route('peserta.notulensi.show', $r->id) }}" class="btn-pill">
                  <i class="fas fa-file-alt"></i> Lihat Notulensi
                </a>
              @else
                <span class="mchip">Notulensi: Belum ada</span>
              @endif

              {{-- Detail --}}
              <a href="{{ route('peserta.rapat.show', $r->id) }}" class="btn-pill primary">
                <i class="fas fa-eye"></i> Detail
              </a>
            </div>
          </div>
        @empty
          <div class="text-muted text-center">Tidak ada data.</div>
        @endforelse
      </div>

      <div class="d-flex justify-content-end mt-3">
          {{ $rapat->links() }}
      </div>
  </div>
</div>
@endsection
