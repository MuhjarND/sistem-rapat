@extends('layouts.app')

@section('title','Dashboard')

@push('style')
<style>
  /* ====== METRIC CARDS ====== */
  .metric-card{
    border: 1px solid var(--border);
    background: linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.02));
    box-shadow: var(--shadow);
    color: var(--text);
    border-radius: 14px;
    overflow: hidden;
  }
  .metric-card .card-body{
    display:flex; align-items:center; gap:14px; padding:18px 18px;
  }
  .metric-icon{
    width:52px;height:52px;border-radius:12px;
    display:flex;align-items:center;justify-content:center;
    background: linear-gradient(180deg, rgba(79,70,229,.25), rgba(14,165,233,.18));
    border:1px solid rgba(99,102,241,.25);
    color:#fff; font-size:22px; box-shadow: var(--shadow);
  }
  .metric-val{font-size:28px; font-weight:800; margin:0; color:#fff;}
  .metric-sub{margin:0; color:var(--muted); font-size:13px; font-weight:600; letter-spacing:.2px;}

  /* ====== SECTION TITLE ====== */
  .section-title{color:#fff; font-weight:800; letter-spacing:.3px; margin:0;}

  /* ====== CARD GENERIC (chart/list) ====== */
  .dash-card{
    background: linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.02));
    border: 1px solid var(--border);
    border-radius: 14px;
    box-shadow: var(--shadow);
    color: var(--text);
  }
  .dash-card .card-header{
    background: transparent; border-bottom:1px solid var(--border);
  }
  .chart-wrap{height: 340px;}  /* samakan tinggi antar chart */

  /* ====== TABLE & LIST ====== */
  .table-mini{ color: var(--text); }
  .table-mini thead th{
    text-align:center; vertical-align:middle;
    background: rgba(79,70,229,.12);
    border-top:none; border-bottom:1px solid var(--border);
    text-transform: uppercase; letter-spacing:.3px; font-size:.75rem;
  }
  .table-mini td{ vertical-align: middle; font-size:.9rem; }
  .table-mini tbody tr:hover{ background: rgba(14,165,233,.06); }

  .list-group-item{
    background: transparent; color: var(--text);
    border-color: var(--border);
  }
  .list-group-item small{ color: var(--muted); }

  /* kecilkan gutter antar kolom di row tertentu jika perlu */
  @media (min-width: 992px){
    .gutter-tight > [class^="col-"]{ padding-right:10px; padding-left:10px; }
  }
</style>
@endpush

@section('content')
<div class="container-fluid">

  {{-- ====== METRIC CARDS ====== --}}
  <div class="row gutter-tight">
    <div class="col-md-3 mb-3">
      <div class="card metric-card">
        <div class="card-body">
          <div class="metric-icon"><i class="fas fa-calendar-alt"></i></div>
          <div>
            <p class="metric-val">{{ number_format($total_rapat) }}</p>
            <p class="metric-sub">Total Rapat</p>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card metric-card">
        <div class="card-body">
          <div class="metric-icon"><i class="fas fa-calendar-check"></i></div>
          <div>
            <p class="metric-val">{{ number_format($rapat_bulan_ini) }}</p>
            <p class="metric-sub">Rapat Bulan Ini</p>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card metric-card">
        <div class="card-body">
          <div class="metric-icon"><i class="fas fa-file-alt"></i></div>
          <div>
            <p class="metric-val">{{ number_format($notulensi_sudah) }}</p>
            <p class="metric-sub">Notulensi Sudah</p>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card metric-card">
        <div class="card-body">
          <div class="metric-icon"><i class="fas fa-folder-open"></i></div>
          <div>
            <p class="metric-val">{{ number_format($total_laporan) }}</p>
            <p class="metric-sub">Total Laporan</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ====== CHARTS ====== --}}
  <div class="row gutter-tight">
    <div class="col-lg-6 mb-3">
      <div class="card dash-card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong class="section-title">Rapat per Bulan ({{ date('Y') }})</strong>
        </div>
        <div class="card-body chart-wrap">
          <canvas id="chartRapatBulan"></canvas>
        </div>
      </div>
    </div>
    <div class="col-lg-6 mb-3">
      <div class="card dash-card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong class="section-title">Top Kategori Rapat</strong>
        </div>
        <div class="card-body chart-wrap">
          <canvas id="chartKategori"></canvas>
        </div>
      </div>
    </div>
  </div>

  {{-- ====== LIST & TABLE ====== --}}
  <div class="row gutter-tight">
    <div class="col-lg-6 mb-3">
      <div class="card dash-card">
        <div class="card-header">
          <strong class="section-title">Rapat Akan Datang (7 hari)</strong>
        </div>
        <div class="card-body p-0">
          <table class="table table-mini table-hover table-sm mb-0">
            <thead>
              <tr>
                <th>Judul</th><th>Tanggal</th><th>Waktu</th><th>Tempat</th>
              </tr>
            </thead>
            <tbody>
              @forelse($rapat_akan_datang as $r)
                <tr>
                  <td>{{ $r->judul }}</td>
                  <td>{{ \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') }}</td>
                  <td>{{ $r->waktu_mulai }}</td>
                  <td>{{ $r->tempat }}</td>
                </tr>
              @empty
                <tr><td colspan="4" class="text-muted text-center p-3">Tidak ada jadwal dalam 7 hari.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-lg-3 mb-3">
      <div class="card dash-card">
        <div class="card-header">
          <strong class="section-title">Rapat Terbaru</strong>
        </div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush">
            @forelse($rapat_terbaru as $r)
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>{{ $r->judul }}</span>
                <small>{{ \Carbon\Carbon::parse($r->tanggal)->format('d/m') }}</small>
              </li>
            @empty
              <li class="list-group-item text-center text-muted">Belum ada</li>
            @endforelse
          </ul>
        </div>
      </div>
    </div>

    <div class="col-lg-3 mb-3">
      <div class="card dash-card">
        <div class="card-header">
          <strong class="section-title">Laporan Terbaru</strong>
        </div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush">
            @forelse($laporan_terbaru as $f)
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>{{ $f->judul }}</span>
                <small>{{ \Carbon\Carbon::parse($f->created_at)->format('d/m') }}</small>
              </li>
            @empty
              <li class="list-group-item text-center text-muted">Belum ada</li>
            @endforelse
          </ul>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
{{-- Chart.js 3.x --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
  // Data dari controller
  const labelBulan = @json($label_bulan);
  const dataBulan  = @json($data_bulan);
  const labelKat   = @json($label_kategori);
  const dataKat    = @json($data_kategori);

  // Palet warna selaras tema (fallback, biar konsisten)
  const cPrimary = '#4f46e5';
  const cPrimary700 = '#4338ca';
  const cBorder = 'rgba(31,42,77,.6)';
  const cGrid   = 'rgba(148,163,184,.15)';

  // ====== Chart Rapat per Bulan ======
  new Chart(document.getElementById('chartRapatBulan'), {
    type: 'bar',
    data: {
      labels: labelBulan,
      datasets: [{
        label: 'Jumlah Rapat',
        data: dataBulan,
        backgroundColor: 'rgba(79,70,229,0.55)',
        borderColor: cPrimary700,
        borderWidth: 1.2,
        borderRadius: 8,
        hoverBackgroundColor: 'rgba(79,70,229,0.75)'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        x: {
          grid: { color: cGrid, borderColor: cBorder, drawBorder: true },
          ticks: { color: '#dbe7ff' }
        },
        y: {
          beginAtZero: true,
          ticks: { precision:0, color: '#dbe7ff' },
          grid: { color: cGrid, borderColor: cBorder, drawBorder: true }
        }
      },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: 'rgba(15,21,51,.95)',
          borderColor: cBorder,
          borderWidth: 1,
          titleColor: '#fff',
          bodyColor: '#cdd9ff'
        }
      }
    }
  });

  // ====== Chart Top Kategori (Horizontal) ======
  new Chart(document.getElementById('chartKategori'), {
    type: 'bar',
    data: {
      labels: labelKat,
      datasets: [{
        label: 'Jumlah Rapat',
        data: dataKat,
        backgroundColor: 'rgba(14,165,233,0.55)',
        borderColor: '#0ea5e9',
        borderWidth: 1.2,
        borderRadius: 8,
        hoverBackgroundColor: 'rgba(14,165,233,0.75)',
        barThickness: 'flex',
        maxBarThickness: 30
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        x: {
          beginAtZero: true,
          ticks: { precision: 0, color: '#dbe7ff' },
          grid: { color: cGrid, borderColor: cBorder, drawBorder: true }
        },
        y: {
          ticks: { autoSkip: false, color: '#dbe7ff' },
          grid: { color: cGrid, borderColor: cBorder, drawBorder: true }
        }
      },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: 'rgba(15,21,51,.95)',
          borderColor: cBorder,
          borderWidth: 1,
          titleColor: '#fff',
          bodyColor: '#cdd9ff'
        }
      },
      layout: { padding: { right: 8 } }
    }
  });
</script>
@endpush
