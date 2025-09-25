@extends('layouts.app')

@section('title','Dashboard Peserta')

@push('style')
<style>
  .metric-card{border:1px solid var(--border);border-radius:14px;background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.02));box-shadow:var(--shadow);color:var(--text)}
  .metric-card .card-body{display:flex;align-items:center;gap:14px;padding:16px 18px}
  .metric-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:linear-gradient(180deg,rgba(79,70,229,.25),rgba(14,165,233,.18));border:1px solid rgba(99,102,241,.25);font-size:20px;color:#fff}
  .metric-val{font-size:26px;font-weight:800;margin:0}
  .metric-sub{margin:0;color:var(--muted);font-size:13px;font-weight:600}
  .dash-card{border:1px solid var(--border);border-radius:14px;background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.02));box-shadow:var(--shadow);color:var(--text)}
  .dash-card .card-header{background:transparent;border-bottom:1px solid var(--border);font-weight:800;color:#fff}
  .list-item{padding:12px 14px;border-bottom:1px solid var(--border)}
  .list-item:last-child{border-bottom:none}
  .list-item .title{font-weight:700}
  .list-item small{color:var(--muted)}
  .table-mini{color:var(--text)}
  .table-mini thead th{background:rgba(79,70,229,.12);border-top:none;border-bottom:1px solid var(--border);text-transform:uppercase;font-size:.75rem;letter-spacing:.3px;text-align:center}
  .table-mini td{vertical-align:middle}
  @media (min-width: 992px){ .gutter-tight>[class^="col-"]{padding-left:10px;padding-right:10px} }
</style>
@endpush

@section('content')
<div class="container-fluid p-0">
  {{-- ====== METRICS ====== --}}
  <div class="row gutter-tight">
    <div class="col-md-3 mb-3">
      <div class="card metric-card">
        <div class="card-body">
          <div class="metric-icon"><i class="fas fa-inbox"></i></div>
          <div>
            <p class="metric-val">{{ $stats['total_diundang'] ?? 0 }}</p>
            <p class="metric-sub">Total Diundang
              @if(($stats['upcoming_count'] ?? 0)>0)
                <span class="badge badge-info ml-2">{{ $stats['upcoming_count'] }} upcoming</span>
              @endif
            </p>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card metric-card">
        <div class="card-body">
          <div class="metric-icon"><i class="fas fa-user-check"></i></div>
          <div>
            <p class="metric-val">{{ $stats['hadir'] ?? 0 }}</p>
            <p class="metric-sub">Hadir</p>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card metric-card">
        <div class="card-body">
          <div class="metric-icon"><i class="fas fa-user-clock"></i></div>
          <div>
            <p class="metric-val">{{ $stats['izin'] ?? 0 }}</p>
            <p class="metric-sub">Izin</p>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card metric-card">
        <div class="card-body">
          <div class="metric-icon"><i class="fas fa-book-open"></i></div>
          <div class="d-flex align-items-center">
            <div>
              <p class="metric-val">{{ $stats['notulensi_tersedia'] ?? 0 }}</p>
              <p class="metric-sub mb-0">Notulensi Tersedia</p>
            </div>
            <a href="{{ route('peserta.rapat') }}#notulensi"
               class="btn btn-sm btn-outline-light ml-auto">Lihat</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ====== Rapat terdekat + Absensi perlu konfirmasi ====== --}}
  <div class="row gutter-tight">
    <div class="col-lg-6 mb-3">
      <div class="card dash-card h-100">
        <div class="card-header d-flex align-items-center">
          <i class="fas fa-bell mr-2"></i> Rapat Terdekat
          <a href="{{ route('peserta.rapat') }}" class="btn btn-sm btn-outline-light ml-auto">Semua</a>
        </div>
        <div class="card-body">
          @if(!empty($rapat_terdekat))
            <div class="list-item d-flex align-items-center">
              <div class="mr-3">
                <span class="btn btn-icon"><i class="far fa-calendar"></i></span>
              </div>
              <div class="flex-fill">
                <div class="title">{{ $rapat_terdekat->judul }}</div>
                <small>
                  {{ \Carbon\Carbon::parse($rapat_terdekat->tanggal)->isoFormat('dddd, D MMM Y') }}
                  • {{ $rapat_terdekat->waktu_mulai }} WIT • {{ $rapat_terdekat->tempat }}
                </small>
              </div>
              <a href="{{ route('peserta.rapat.show', $rapat_terdekat->id) }}"
                 class="btn btn-sm btn-primary ml-3">
                <i class="fas fa-eye mr-1"></i> Detail Rapat
              </a>
            </div>
          @else
            <div class="text-muted">Tidak ada jadwal dekat.</div>
          @endif
        </div>
      </div>
    </div>

    <div class="col-lg-6 mb-3">
      <div class="card dash-card h-100">
        <div class="card-header d-flex align-items-center">
          <i class="fas fa-check-circle mr-2"></i> Absensi Perlu Konfirmasi
          @if(($absensi_pending->count() ?? 0)>0)
            <span class="badge badge-danger ml-2">{{ $absensi_pending->count() }}</span>
          @endif
        </div>
        <div class="card-body p-0">
          @forelse($absensi_pending as $r)
            <div class="list-item d-flex align-items-center">
              <div class="mr-3 text-warning"><i class="fas fa-exclamation-circle"></i></div>
              <div class="flex-fill">
                <div class="title">{{ $r->judul }}</div>
                <small>
                  {{ \Carbon\Carbon::parse($r->tanggal)->isoFormat('ddd, D MMM Y') }}
                  • {{ $r->waktu_mulai }} WIT • {{ $r->tempat }}
                </small>
              </div>
              {{-- Arahkan ke scan token; fallback ke form peserta bila token_qr kosong --}}
              <a href="{{ $r->token_qr ? route('absensi.scan', $r->token_qr) : route('peserta.absensi', $r->id) }}"
                 class="btn btn-sm btn-outline-light">Konfirmasi</a>
            </div>
          @empty
            <div class="p-3 text-muted">Tidak ada yang perlu dikonfirmasi.</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  {{-- ====== Rapat Akan Datang & Riwayat ====== --}}
  <div class="row gutter-tight">
    <div class="col-lg-6 mb-3">
      <div class="card dash-card h-100">
        <div class="card-header"><i class="far fa-calendar-alt mr-2"></i> Rapat Akan Datang (7 hari)</div>
        <div class="card-body p-0">
          <table class="table table-mini table-hover mb-0">
            <thead>
              <tr>
                <th>Judul</th>
                <th>Tanggal</th>
                <th>Tempat</th>
                <th style="width:120px">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse($rapat_akan_datang as $r)
                <tr>
                  <td>{{ $r->judul }}</td>
                  <td class="text-center">{{ \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') }} <br>{{ $r->waktu_mulai }}</td>
                  <td class="text-center">{{ $r->tempat }}</td>
                  <td class="text-center">
                    <a href="{{ route('peserta.rapat.show', $r->id) }}" class="btn btn-sm btn-primary">Detail</a>
                  </td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center text-muted p-3">Tidak ada jadwal dalam 7 hari.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-lg-6 mb-3" id="notulensi">
      <div class="card dash-card h-100">
        <div class="card-header"><i class="fas fa-history mr-2"></i> Riwayat Rapat Terbaru</div>
        <div class="card-body p-0">
          <table class="table table-mini table-hover mb-0">
            <thead>
              <tr>
                <th>Judul</th>  
                <th class="text-center">Absensi</th>
                <th class="text-center">Notulensi</th>
              </tr>
            </thead>
            <tbody>
              @forelse($riwayat_rapat as $r)
                <tr>
                  <td>{{ $r->judul }} <br> {{ \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') }}</td>
                  <td class="text-center">
                    @if($r->absensi_status)
                      <span class="badge badge-success">{{ strtoupper($r->absensi_status) }}</span>
                    @else
                      <a href="{{ $r->token_qr ? route('absensi.scan', $r->token_qr) : route('peserta.absensi', $r->id) }}"
                         class="btn btn-sm btn-outline-light">Konfirmasi</a>
                    @endif
                  </td>
                  <td class="text-center">
                    @if((int)$r->ada_notulensi === 1)
                      <a href="{{ route('peserta.notulensi.show', $r->id) }}" class="btn btn-sm btn-info">Lihat</a>
                    @else
                      <span class="badge badge-secondary">Belum ada</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="4" class="text-center text-muted p-3">Belum ada riwayat.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
