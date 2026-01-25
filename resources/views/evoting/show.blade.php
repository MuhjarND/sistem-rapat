@extends('layouts.app')

@section('title', 'Detail E-Voting')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1">{{ $evoting->judul }}</h3>
        <div class="text-muted">{{ $evoting->deskripsi ?: 'Tanpa deskripsi' }}</div>
    </div>
    <div class="d-flex align-items-center">
        <form action="{{ route('evoting.status', $evoting->id) }}" method="POST" class="mr-2">
            @csrf
            <input type="hidden" name="status" value="{{ $evoting->status === 'open' ? 'closed' : 'open' }}">
            <button type="submit" class="btn btn-outline-light btn-sm">
                {{ $evoting->status === 'open' ? 'Tutup Voting' : 'Buka Voting' }}
            </button>
        </form>
        <a href="{{ route('evoting.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
    </div>
</div>

@if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
@if($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif

<div class="card mb-3">
    <div class="card-body d-flex justify-content-between align-items-center">
        <div>
            <span class="badge {{ $evoting->status === 'open' ? 'badge-success' : 'badge-secondary' }}">
                {{ strtoupper($evoting->status) }}
            </span>
            <span class="text-muted ml-2">Dibuat: {{ \Carbon\Carbon::parse($evoting->created_at)->format('d/m/Y H:i') }}</span>
        </div>
        <form action="{{ route('evoting.sendLinks', $evoting->id) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-paper-plane mr-1"></i> Kirim Link Voting
            </button>
        </form>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between">
        <div class="mr-md-4 mb-3 mb-md-0">
            <div class="text-muted mb-1">Link Voting Publik</div>
            <a class="evoting-link" href="{{ route('evoting.public', $evoting->public_token) }}" target="_blank">
                {{ route('evoting.public', $evoting->public_token) }}
            </a>
            <div class="text-muted mt-2">Gunakan satu link ini untuk seluruh peserta.</div>
        </div>
        <div class="evoting-qr text-center ml-md-auto">
            <div class="text-muted mb-2">Scan QR untuk voting</div>
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ rawurlencode(route('evoting.public', $evoting->public_token)) }}"
                 alt="QR Voting" width="180" height="180">
            <div class="mt-2">
                <button type="button" class="btn btn-outline-light btn-sm" data-toggle="modal" data-target="#qrModal">
                    <i class="fas fa-search-plus mr-1"></i> Perbesar
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="qrModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content modal-solid">
      <div class="modal-header">
        <h5 class="modal-title">QR Voting</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body text-center">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=520x520&data={{ rawurlencode(route('evoting.public', $evoting->public_token)) }}"
             alt="QR Voting" width="480" height="480">
      </div>
    </div>
  </div>
</div>

<div class="row evoting-result-grid">
@foreach($items as $item)
    @php
        $itemCandidates = $candidatesByItem->get($item->id, collect());
        $totalVotes = $itemCandidates->sum(function($c) use ($voteCounts){ return (int) ($voteCounts[$c->id] ?? 0); });
    @endphp
    <div class="col-lg-4 col-md-6 mb-3">
        <div class="card h-100 evoting-chart-card">
            <div class="card-header">
                <strong>{{ $item->judul }}</strong>
            <span class="text-muted ml-2">Total suara: <span data-item-total="{{ $item->id }}">{{ $totalVotes }}/{{ $voters->count() }}</span></span>
            </div>
            <div class="card-body">
                <div class="mb-3 evoting-chart">
                    <canvas id="chart-item-{{ $item->id }}" height="160"></canvas>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                        <tr>
                            <th>Kandidat</th>
                            <th>Suara</th>
                            <th>Persentase</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($itemCandidates as $cand)
                            @php
                                $count = (int) ($voteCounts[$cand->id] ?? 0);
                                $pct = $totalVotes > 0 ? round(($count / $totalVotes) * 100, 1) : 0;
                            @endphp
                            <tr data-candidate-row="{{ $cand->id }}">
                                <td class="text-left">{{ $cand->nama }}</td>
                                <td class="text-center" data-candidate-count="{{ $cand->id }}">{{ $count }}</td>
                                <td class="text-center" data-candidate-percent="{{ $cand->id }}">{{ $pct }}%</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endforeach
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Daftar Peserta Voting</strong>
        <span class="text-muted">Total: {{ $voters->count() }}</span>
    </div>
    <div class="card-body data-scroll">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>Nama</th>
                    <th>Jabatan/Unit</th>
                    <th>WA</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                @foreach($voters as $voter)
                    <tr>
                        <td class="text-left">{{ $voter->name }}</td>
                        <td class="text-left">
                            {{ $voter->jabatan ?? '-' }}{{ $voter->unit ? ' / '.$voter->unit : '' }}
                        </td>
                        <td class="text-center">{{ $voter->no_hp ?? '-' }}</td>
                        <td class="text-center" data-voter-status="{{ $voter->id }}">
                            @if($voter->voted_at)
                                <span class="badge badge-success">Sudah</span>
                            @else
                                <span class="badge badge-warning">Belum</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
  .evoting-link{ font-weight:700; color:#7aa2ff; word-break:break-all; }
  .evoting-qr img{ border-radius:12px; background:#fff; padding:8px; box-shadow: 0 10px 28px rgba(0,0,0,.25); }
  .evoting-chart-card .card-header{ background: rgba(255,255,255,.04); border-bottom:1px solid rgba(255,255,255,.08); }
  .evoting-chart-card .card-body{ background: rgba(255,255,255,.02); }
  .evoting-chart{ min-height: 200px; }
  .winner-row{ background: rgba(34,197,94,.15); }
  .evoting-result-grid .card{ border:1px solid rgba(122,162,255,.25); }
  .evoting-result-grid .card-header{ font-size:.95rem; }
  .pulse-update{
    animation: pulseUpdate 1s ease;
  }
  @keyframes pulseUpdate{
    0%{ background-color: rgba(254,231,21,.25); }
    100%{ background-color: transparent; }
  }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
  (function(){
    var resultUrl = @json(route('evoting.results', $evoting->id));
    var charts = {};

    function buildChart(itemId, labels, values, colors){
      var ctx = document.getElementById('chart-item-' + itemId);
      if (!ctx) return;
      var chart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [{
            label: 'Suara',
            data: values,
            backgroundColor: colors,
            borderColor: colors,
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          indexAxis: 'y',
          plugins: {
            legend: { display: false }
          },
          scales: {
            x: {
              beginAtZero: true,
              ticks: { precision: 0, color: '#e6eefc' },
              grid: { color: 'rgba(255,255,255,.08)' }
            },
            y: {
              ticks: { color: '#e6eefc' },
              grid: { color: 'rgba(255,255,255,.08)' }
            }
          },
          animation: {
            duration: 800,
            easing: 'easeOutQuart'
          }
        }
      });
      charts[itemId] = chart;
    }

    var prevCounts = {};

    function updateCharts(payload){
      if (!payload || !payload.items) return;
      var totalVoters = Array.isArray(payload.voters) ? payload.voters.length : 0;
      var votedCount = Array.isArray(payload.voters) ? payload.voters.filter(function(v){ return v.voted; }).length : 0;

      payload.items.forEach(function(item){
        var labels = item.candidates.map(function(c){ return c.name; });
        var values = item.candidates.map(function(c){ return c.total; });
        var maxVal = 0;
        values.forEach(function(v){ if (v > maxVal) maxVal = v; });
        var colors = values.map(function(v){
          return v === maxVal && maxVal > 0
            ? 'rgba(34,197,94,.85)'
            : 'rgba(79,70,229,.55)';
        });

        if (!charts[item.id]) {
          buildChart(item.id, labels, values, colors);
        } else {
          charts[item.id].data.labels = labels;
          charts[item.id].data.datasets[0].data = values;
          charts[item.id].data.datasets[0].backgroundColor = colors;
          charts[item.id].data.datasets[0].borderColor = colors;
          charts[item.id].update();
        }

        var totalEl = document.querySelector('[data-item-total="' + item.id + '"]');
        if (totalEl) totalEl.textContent = (item.total || votedCount) + '/' + totalVoters;

        item.candidates.forEach(function(c){
          var countEl = document.querySelector('[data-candidate-count="' + c.id + '"]');
          if (countEl) {
            var prev = prevCounts[c.id];
            countEl.textContent = c.total;
            if (typeof prev === 'number' && prev !== c.total) {
              countEl.classList.add('pulse-update');
              setTimeout(function(){ countEl.classList.remove('pulse-update'); }, 1000);
            }
          }
          var pctEl = document.querySelector('[data-candidate-percent="' + c.id + '"]');
          if (pctEl) {
            pctEl.textContent = c.percent + '%';
            if (typeof prevCounts[c.id] === 'number' && prevCounts[c.id] !== c.total) {
              pctEl.classList.add('pulse-update');
              setTimeout(function(){ pctEl.classList.remove('pulse-update'); }, 1000);
            }
          }
          var rowEl = document.querySelector('[data-candidate-row="' + c.id + '"]');
          if (rowEl) {
            if (c.total === maxVal && maxVal > 0) {
              rowEl.classList.add('winner-row');
            } else {
              rowEl.classList.remove('winner-row');
            }
          }
          prevCounts[c.id] = c.total;
        });
      });

      if (Array.isArray(payload.voters)) {
        payload.voters.forEach(function(v){
          var cell = document.querySelector('[data-voter-status="' + v.id + '"]');
          if (!cell) return;
          cell.innerHTML = v.voted
            ? '<span class="badge badge-success">Sudah</span>'
            : '<span class="badge badge-warning">Belum</span>';
        });
      }
    }

    function fetchResults(){
      fetch(resultUrl, { headers: { 'Accept': 'application/json' }})
        .then(function(res){ return res.json(); })
        .then(updateCharts)
        .catch(function(){});
    }

    fetchResults();
    setInterval(fetchResults, 5000);
  })();
</script>
@endpush
