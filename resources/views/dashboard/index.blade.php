@extends('layouts.app')

@section('title','Dashboard')

@section('style')
<style>
  :root{
    --primary: #082C38;
    --accent:  #C19976;
    --soft:    #FCD8B4;
  }
  .dash-card{
    border: 0;
    border-radius: 10px;
    box-shadow: 0 8px 20px rgba(0,0,0,.06);
    overflow: hidden;
  }
  .dash-card .card-body{
    display:flex; align-items:center; gap:14px;
  }
  .dash-icon{
    width:48px;height:48px;border-radius:10px;
    display:flex;align-items:center;justify-content:center;
    background: var(--soft); color: var(--primary); font-size:22px;
  }
  .dash-val{font-size:26px; font-weight:700; margin:0; color:#222;}
  .dash-sub{margin:0; color:#777; font-size:13px;}
  .section-title{color:#333; font-weight:700;}

  /* — memastikan tinggi area chart sama — */
  .chart-wrap{height: 340px;}   /* ubah angka ini kalau mau lebih kecil/besar */
  .table-mini td, .table-mini th{padding:.5rem .75rem;}
</style>
@endsection

@section('content')
<div class="container-fluid">

  {{-- Cards Statistik --}}
  <div class="row">
    <div class="col-md-3 mb-3">
      <div class="card dash-card">
        <div class="card-body">
          <div class="dash-icon"><i class="fas fa-calendar-alt"></i></div>
          <div>
            <p class="dash-val">{{ number_format($total_rapat) }}</p>
            <p class="dash-sub">Total Rapat</p>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card dash-card">
        <div class="card-body">
          <div class="dash-icon"><i class="fas fa-calendar-check"></i></div>
          <div>
            <p class="dash-val">{{ number_format($rapat_bulan_ini) }}</p>
            <p class="dash-sub">Rapat Bulan Ini</p>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card dash-card">
        <div class="card-body">
          <div class="dash-icon"><i class="fas fa-file-alt"></i></div>
          <div>
            <p class="dash-val">{{ number_format($notulensi_sudah) }}</p>
            <p class="dash-sub">Notulensi Sudah</p>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card dash-card">
        <div class="card-body">
          <div class="dash-icon"><i class="fas fa-folder-open"></i></div>
          <div>
            <p class="dash-val">{{ number_format($total_laporan) }}</p>
            <p class="dash-sub">Total Laporan</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Charts — ukuran sama (6-6) & tinggi seragam --}}
  <div class="row">
    <div class="col-lg-6 mb-3">
      <div class="card dash-card">
        <div class="card-header bg-white">
          <strong class="section-title">Rapat per Bulan ({{ date('Y') }})</strong>
        </div>
        <div class="card-body chart-wrap">
          <canvas id="chartRapatBulan"></canvas>
        </div>
      </div>
    </div>
    <div class="col-lg-6 mb-3">
      <div class="card dash-card">
        <div class="card-header bg-white">
          <strong class="section-title">Top Kategori Rapat</strong>
        </div>
        <div class="card-body chart-wrap">
          <canvas id="chartKategori"></canvas>
        </div>
      </div>
    </div>
  </div>

  {{-- Lists --}}
  <div class="row">
    <div class="col-lg-6 mb-3">
      <div class="card dash-card">
        <div class="card-header bg-white">
          <strong class="section-title">Rapat Akan Datang (7 hari)</strong>
        </div>
        <div class="card-body p-0">
          <table class="table table-mini mb-0">
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
        <div class="card-header bg-white">
          <strong class="section-title">Rapat Terbaru</strong>
        </div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush">
            @forelse($rapat_terbaru as $r)
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>{{ $r->judul }}</span>
                <small class="text-muted">{{ \Carbon\Carbon::parse($r->tanggal)->format('d/m') }}</small>
              </li>
            @empty
              <li class="list-group-item text-muted text-center">Belum ada</li>
            @endforelse
          </ul>
        </div>
      </div>
    </div>

    <div class="col-lg-3 mb-3">
      <div class="card dash-card">
        <div class="card-header bg-white">
          <strong class="section-title">Laporan Terbaru</strong>
        </div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush">
            @forelse($laporan_terbaru as $f)
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>{{ $f->judul }}</span>
                <small class="text-muted">{{ \Carbon\Carbon::parse($f->created_at)->format('d/m') }}</small>
              </li>
            @empty
              <li class="list-group-item text-muted text-center">Belum ada</li>
            @endforelse
          </ul>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
  // Data dari controller
  const labelBulan = @json($label_bulan);
  const dataBulan  = @json($data_bulan);
  const labelKat   = @json($label_kategori);
  const dataKat    = @json($data_kategori);

  // Chart Rapat per Bulan (bar vertikal)
  new Chart(document.getElementById('chartRapatBulan'), {
    type: 'bar',
    data: {
      labels: labelBulan,
      datasets: [{
        label: 'Jumlah Rapat',
        data: dataBulan,
        backgroundColor: '#C19976',
        borderColor: '#082C38',
        borderWidth: 1,
        borderRadius: 6,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,   // mengikuti .chart-wrap
      scales: { y: { beginAtZero: true, ticks: { precision:0 } } },
      plugins: { legend: { display: false } }
    }
  });

  // Chart Top Kategori (bar horizontal) — ukuran sama dengan chart pertama
  new Chart(document.getElementById('chartKategori'), {
    type: 'bar',
    data: {
      labels: labelKat,
      datasets: [{
        label: 'Jumlah Rapat',
        data: dataKat,
        backgroundColor: '#C19976',
        borderColor: '#082C38',
        borderWidth: 1,
        borderRadius: 6,
        barThickness: 'flex',
        maxBarThickness: 28
      }]
    },
    options: {
      indexAxis: 'y',               // horizontal
      responsive: true,
      maintainAspectRatio: false,   // mengikuti .chart-wrap
      scales: {
        x: { beginAtZero: true, ticks: { precision: 0 } },
        y: { ticks: { autoSkip: false } }
      },
      plugins: {
        legend: { display: false },
        tooltip: { mode: 'nearest', intersect: false }
      },
      layout: { padding: { right: 8 } }
    }
  });
</script>
@endsection
