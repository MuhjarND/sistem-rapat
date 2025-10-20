@extends('layouts.app')
@section('title','Dashboard Notulensi')

@push('style')
<style>
  .metric-card{border:1px solid var(--border);border-radius:14px;background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.02));box-shadow:var(--shadow);color:var(--text)}
  .metric-card .card-body{display:flex;align-items:center;gap:14px;padding:16px 18px}
  .metric-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:linear-gradient(180deg,rgba(79,70,229,.25),rgba(14,165,233,.18));border:1px solid rgba(99,102,241,.25);font-size:20px;color:#fff}
  .metric-val{font-size:26px;font-weight:800;margin:0}
  .metric-sub{margin:0;color:var(--muted);font-size:13px;font-weight:600}

  .dash-card{border:1px solid var(--border);border-radius:14px;background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.02));box-shadow:var(--shadow);color:var(--text)}
  .dash-card .card-header{background:transparent;border-bottom:1px solid var(--border);font-weight:800;color:#fff}

  .table-mini{color:var(--text)}
  .table-mini thead th{background:rgba(79,70,229,.12);border-top:none;border-bottom:1px solid var(--border);text-transform:uppercase;font-size:.75rem;letter-spacing:.3px;text-align:center}
  .table-mini td{vertical-align:middle}

  .chip{display:inline-flex;align-items:center;gap:.4rem;border-radius:999px;padding:.25rem .55rem;font-size:.72rem;font-weight:800;line-height:1;border:1px solid rgba(255,255,255,.18)}
  .chip.warn{background:#f59e0b;color:#111}
  .chip.good{background:#22c55e;color:#fff}
  .chip.info{background:#0ea5e9;color:#fff}

  @media (min-width: 992px){ .gutter-tight>[class^="col-"]{padding-left:10px;padding-right:10px} }

  /* ====== MOBILE TABLE → CARD ====== */
  @media (max-width: 575.98px){
    .table-mini thead{ display:none; }
    .table-mini tbody tr{
      display:block;
      border:1px solid var(--border);
      border-radius:12px;
      background:rgba(255,255,255,.02);
      margin:10px 12px;
      overflow:hidden;
    }
    .table-mini tbody td{
      display:block;
      width:100%;
      border:0!important;
      border-bottom:1px solid var(--border)!important;
      padding:.7rem .9rem !important;
      text-align:left !important;
    }
    .table-mini tbody td:last-child{ border-bottom:0!important; }
    .table-mini tbody td[data-label]::before{
      content: attr(data-label);
      display:block;
      font-size:.72rem;
      font-weight:800;
      letter-spacing:.2px;
      color:#9fb0cd;
      text-transform:uppercase;
      margin-bottom:6px;
    }
  }
</style>
@endpush

@section('content')
<div class="container-fluid p-0">

  {{-- ====== METRICS ====== --}}
  <div class="row gutter-tight">
    <div class="col-md-3 mb-3">
      <div class="card metric-card">
        <div class="card-body">
          <div class="metric-icon"><i class="fas fa-calendar-check"></i></div>
          <div>
            <p class="metric-val">{{ $metrics['totalRapat'] }}</p>
            <p class="metric-sub">Total Rapat</p>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card metric-card">
        <div class="card-body">
          <div class="metric-icon"><i class="fas fa-book-open"></i></div>
          <div>
            <p class="metric-val">{{ $metrics['totalNotulensi'] }}</p>
            <p class="metric-sub">Total Notulensi</p>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card metric-card">
        <div class="card-body">
          <div class="metric-icon"><i class="fas fa-times-circle"></i></div>
          <div class="d-flex align-items-center w-100">
            <div>
              <p class="metric-val">{{ $metrics['belumAda'] }}</p>
              <p class="metric-sub mb-0">Belum Ada</p>
            </div>
            <a href="{{ route('notulensi.belum') }}" class="btn btn-sm btn-outline-light ml-auto">Lihat</a>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card metric-card">
        <div class="card-body">
          <div class="metric-icon"><i class="fas fa-check-circle"></i></div>
          <div class="d-flex align-items-center w-100">
            <div>
              <p class="metric-val">{{ $metrics['sudahAda'] }}</p>
              <p class="metric-sub mb-0">Sudah Ada</p>
            </div>
            <a href="{{ route('notulensi.sudah') }}" class="btn btn-sm btn-outline-light ml-auto">Lihat</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ====== PENDING & SUDAH ====== --}}
  <div class="row gutter-tight">
    <div class="col-lg-6 mb-3">
      <div class="card dash-card h-100">
        <div class="card-header d-flex align-items-center">
          <i class="fas fa-clock mr-2"></i> Rapat Tanpa Notulensi (Terbaru)
          <a href="{{ route('notulensi.belum') }}" class="btn btn-sm btn-outline-light ml-auto">Kelola</a>
        </div>
        <div class="card-body p-0">
          <table class="table table-mini table-hover mb-0">
            <thead>
              <tr>
                <th>Judul</th>
                <th class="text-center">Tanggal</th>
                <th class="text-center">Waktu</th>
                <th>Tempat</th>
                <th class="text-center">Status</th>
              </tr>
            </thead>
            <tbody>
              @forelse($pending as $r)
                <tr>
                  <td data-label="Judul">{{ $r->judul }}</td>
                  <td class="text-center" data-label="Tanggal">{{ \Carbon\Carbon::parse($r->tanggal)->isoFormat('D MMM Y') }}</td>
                  <td class="text-center" data-label="Waktu">{{ $r->waktu_mulai }}</td>
                  <td data-label="Tempat">{{ $r->tempat }}</td>
                  <td class="text-center" data-label="Status"><span class="chip warn"><i class="fas fa-exclamation-circle"></i> Belum</span></td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center text-muted p-3">Semua rapat sudah memiliki notulensi. 🎉</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-lg-6 mb-3">
      <div class="card dash-card h-100">
        <div class="card-header d-flex align-items-center">
          <i class="fas fa-list mr-2"></i> Notulensi Terbaru
          <a href="{{ route('notulensi.sudah') }}" class="btn btn-sm btn-outline-light ml-auto">Lihat Semua</a>
        </div>
        <div class="card-body p-0">
          <table class="table table-mini table-hover mb-0">
            <thead>
              <tr>
                <th>Judul</th>
                <th class="text-center">Tanggal</th>
                <th class="text-center">Waktu</th>
                <th>Tempat</th>
                <th class="text-center">Status</th>
              </tr>
            </thead>
            <tbody>
              @forelse($selesai as $r)
                <tr>
                  <td data-label="Judul">{{ $r->judul }}</td>
                  <td class="text-center" data-label="Tanggal">{{ \Carbon\Carbon::parse($r->tanggal)->isoFormat('D MMM Y') }}</td>
                  <td class="text-center" data-label="Waktu">{{ $r->waktu_mulai }}</td>
                  <td data-label="Tempat">{{ $r->tempat }}</td>
                  <td class="text-center" data-label="Status"><span class="chip good"><i class="fas fa-check"></i> Ada</span></td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center text-muted p-3">Belum ada notulensi tercatat.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  {{-- ====== TREND RINGKAS (6 bulan) ====== --}}
  <div class="row gutter-tight">
    <div class="col-12 mb-3">
      <div class="card dash-card">
        <div class="card-header d-flex align-items-center">
          <i class="fas fa-chart-line mr-2"></i> Produktivitas Notulensi (6 Bulan Terakhir)
        </div>
        <div class="card-body">
          @if($byMonth->count())
            <div class="d-flex flex-wrap" style="gap:10px;">
              @foreach($byMonth as $ym => $tot)
                <div class="chip info">
                  {{ \Carbon\Carbon::createFromFormat('Y-m', $ym)->isoFormat('MMM Y') }}: {{ $tot }}
                </div>
              @endforeach
            </div>
          @else
            <div class="text-muted">Belum ada data.</div>
          @endif
        </div>
      </div>
    </div>
  </div>

</div>
@endsection
