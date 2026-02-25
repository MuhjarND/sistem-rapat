@extends('layouts.app')

@section('title', 'Detail E-Voting')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1">{{ $evoting->judul }}</h3>
        <div class="text-muted">{!! nl2br(e($evoting->deskripsi ?: 'Tanpa deskripsi')) !!}</div>
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
            <div class="text-muted mt-2">
                Gunakan satu link ini untuk seluruh peserta.
                <a href="{{ route('evoting.public.results', $evoting->public_token) }}" target="_blank" class="ml-2">Lihat hasil publik</a>
            </div>
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
        <div class="card h-100 evoting-chart-card" data-item-card="{{ $item->id }}">
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

<div id="evotingComplete" class="evoting-complete" aria-hidden="true">
    <div class="evoting-complete-card">
        <div class="evoting-complete-badge">&#10003;</div>
        <div class="evoting-complete-title">Semua peserta sudah voting</div>
        <div class="evoting-complete-sub">Terima kasih atas partisipasinya.</div>
        <button type="button" class="btn btn-outline-light btn-sm mt-3" id="btnCloseComplete">Tutup</button>
        <div class="evoting-confetti">
            <span></span><span></span><span></span><span></span><span></span><span></span>
            <span></span><span></span><span></span><span></span><span></span><span></span>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
  .evoting-link{ font-weight:700; color:#7aa2ff; word-break:break-all; }
  .evoting-qr img{ border-radius:12px; background:#fff; padding:8px; box-shadow: 0 10px 28px rgba(0,0,0,.25); }
  .evoting-chart-card{
    perspective: 1200px;
    transform-style: preserve-3d;
    transition: transform .45s cubic-bezier(.2,.8,.2,1), box-shadow .45s cubic-bezier(.2,.8,.2,1);
    will-change: transform;
  }
  .evoting-chart-card:hover{
    transform: translateY(-10px) rotateX(4deg) rotateY(-4deg);
    box-shadow: 0 24px 60px rgba(10,18,38,.55);
  }
  .evoting-chart-card .card-header{ background: rgba(255,255,255,.04); border-bottom:1px solid rgba(255,255,255,.08); }
  .evoting-chart-card .card-body{ background: rgba(255,255,255,.02); transform-style: preserve-3d; }
  .evoting-chart{ min-height: 200px; }
  .winner-row{ background: rgba(34,197,94,.15); }
  .evoting-result-grid .card{ border:1px solid rgba(122,162,255,.25); }
  .evoting-result-grid .card-header{ font-size:.95rem; }
  .evoting-chart canvas{
    animation: floatChart 9s ease-in-out infinite;
    filter: drop-shadow(0 10px 16px rgba(4,10,30,.35));
  }
  @keyframes floatChart{
    0%,100%{ transform: translateY(0); }
    50%{ transform: translateY(-10px); }
  }
  .evoting-chart-card table tbody tr{
    transition: transform .35s cubic-bezier(.2,.8,.2,1), box-shadow .35s cubic-bezier(.2,.8,.2,1), background .35s ease;
  }
  .evoting-chart-card table tbody tr:hover{
    transform: translateZ(12px) scale(1.02);
    background: rgba(122,162,255,.16);
    box-shadow: inset 0 0 0 1px rgba(122,162,255,.5);
  }
  .pulse-update{
    animation: pulseUpdate 1s ease;
  }
  @keyframes pulseUpdate{
    0%{ background-color: rgba(254,231,21,.25); }
    100%{ background-color: transparent; }
  }
  .evoting-chart-card.update-glow{
    animation: glowPulse 1.3s ease;
    box-shadow: 0 0 0 1px rgba(254,231,21,.6), 0 0 38px rgba(254,231,21,.45);
  }
  @keyframes glowPulse{
    0%{ box-shadow: 0 0 0 1px rgba(254,231,21,.9), 0 0 44px rgba(254,231,21,.65); }
    100%{ box-shadow: 0 0 0 1px rgba(254,231,21,.2), 0 0 14px rgba(254,231,21,.25); }
  }
  .evoting-complete{
    position: fixed; inset: 0; z-index: 2100;
    display: none; align-items: center; justify-content: center;
    background: rgba(8,12,28,.75);
    backdrop-filter: blur(6px);
  }
  .evoting-complete.show{ display:flex; }
  .evoting-complete-card{
    position: relative;
    background: linear-gradient(160deg, rgba(17,24,39,.96), rgba(15,23,42,.9));
    border: 1px solid rgba(122,162,255,.35);
    border-radius: 18px;
    padding: 28px 26px;
    text-align: center;
    color: #fff;
    box-shadow: 0 18px 50px rgba(0,0,0,.45);
    animation: popIn .6s ease;
    overflow: hidden;
  }
  .evoting-complete-badge{
    width: 72px; height: 72px; border-radius: 50%;
    display:flex; align-items:center; justify-content:center;
    background: rgba(34,197,94,.2);
    border: 2px solid rgba(34,197,94,.6);
    color: #22c55e; font-size: 36px; margin: 0 auto 12px;
    animation: badgePulse 1.2s ease infinite;
  }
  .evoting-complete-title{ font-weight:800; font-size:20px; }
  .evoting-complete-sub{ color:#cbd5f5; margin-top:6px; }
  .evoting-confetti span{
    position:absolute; width:10px; height:10px; border-radius:2px;
    background: #fbbf24;
    animation: confetti 1.8s ease infinite;
    opacity: .9;
  }
  .evoting-confetti span:nth-child(2){ background:#22c55e; left:12%; top:10%; animation-delay:.1s; }
  .evoting-confetti span:nth-child(3){ background:#60a5fa; left:85%; top:15%; animation-delay:.2s; }
  .evoting-confetti span:nth-child(4){ background:#f472b6; left:20%; top:70%; animation-delay:.3s; }
  .evoting-confetti span:nth-child(5){ background:#a78bfa; left:78%; top:60%; animation-delay:.4s; }
  .evoting-confetti span:nth-child(6){ background:#fbbf24; left:50%; top:8%; animation-delay:.5s; }
  .evoting-confetti span:nth-child(7){ background:#22c55e; left:6%; top:40%; animation-delay:.6s; }
  .evoting-confetti span:nth-child(8){ background:#60a5fa; left:92%; top:45%; animation-delay:.7s; }
  .evoting-confetti span:nth-child(9){ background:#f472b6; left:30%; top:85%; animation-delay:.8s; }
  .evoting-confetti span:nth-child(10){ background:#a78bfa; left:65%; top:78%; animation-delay:.9s; }
  .evoting-confetti span:nth-child(11){ background:#fbbf24; left:40%; top:20%; animation-delay:1s; }
  .evoting-confetti span:nth-child(12){ background:#22c55e; left:60%; top:25%; animation-delay:1.1s; }
  @keyframes popIn{ 0%{ transform: scale(.9); opacity:0; } 100%{ transform: scale(1); opacity:1; } }
  @keyframes badgePulse{ 0%,100%{ transform: scale(1); } 50%{ transform: scale(1.05); } }
  @keyframes confetti{
    0%{ transform: translateY(0) rotate(0deg); opacity:.9; }
    100%{ transform: translateY(-30px) rotate(160deg); opacity:.2; }
  }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
  (function(){
    var resultUrl = @json(route('evoting.results', $evoting->id));
    var charts = {};
    var completionShown = false;
    var shadowPlugin = {
      id: 'shadowBars',
      beforeDatasetsDraw: function(chart){
        var ctx = chart.ctx;
        ctx.save();
        ctx.shadowColor = 'rgba(10,18,38,.6)';
        ctx.shadowBlur = 18;
        ctx.shadowOffsetX = 6;
        ctx.shadowOffsetY = 8;
      },
      afterDatasetsDraw: function(chart){
        chart.ctx.restore();
      }
    };

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
            borderWidth: 1,
            borderRadius: 8,
            barThickness: 16
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
        },
        plugins: [shadowPlugin]
      });
      charts[itemId] = chart;
    }

    var prevCounts = {};

    function updateCharts(payload){
      if (!payload || !payload.items) return;
      var totalVoters = Array.isArray(payload.voters) ? payload.voters.length : 0;
      var votedCount = Array.isArray(payload.voters) ? payload.voters.filter(function(v){ return v.voted; }).length : 0;

      payload.items.forEach(function(item){
        var itemChanged = false;
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
              itemChanged = true;
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

        if (itemChanged) {
          var cardEl = document.querySelector('[data-item-card="' + item.id + '"]');
          if (cardEl) {
            cardEl.classList.add('update-glow');
            setTimeout(function(){ cardEl.classList.remove('update-glow'); }, 1000);
          }
        }
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

      if (totalVoters > 0 && votedCount === totalVoters && !completionShown) {
        completionShown = true;
        var popup = document.getElementById('evotingComplete');
        if (popup) {
          popup.classList.add('show');
          popup.setAttribute('aria-hidden', 'false');
        }
      }
    }

    function fetchResults(){
      fetch(resultUrl, { headers: { 'Accept': 'application/json' }})
        .then(function(res){ return res.json(); })
        .then(updateCharts)
        .catch(function(){});
    }

    var btnClose = document.getElementById('btnCloseComplete');
    if (btnClose) {
      btnClose.addEventListener('click', function(){
        var popup = document.getElementById('evotingComplete');
        if (popup) {
          popup.classList.remove('show');
          popup.setAttribute('aria-hidden', 'true');
        }
      });
    }

    fetchResults();
    setInterval(fetchResults, 5000);
  })();
</script>
@endpush
