<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Hasil E-Voting - {{ $evoting->judul }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        :root{
            --bg:#0b1220;
            --panel:rgba(255,255,255,.05);
            --line:rgba(255,255,255,.1);
            --text:#e8eefb;
            --muted:#9eb0d3;
            --accent:#7aa2ff;
            --ok:#22c55e;
            --warn:#f59e0b;
        }
        body{
            margin:0;
            background: radial-gradient(1200px 480px at 10% -20%, rgba(122,162,255,.2), transparent), var(--bg);
            color:var(--text);
            font-family: "Inter", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        }
        .wrap{ max-width: 1200px; margin: 28px auto; padding: 0 16px; }
        .card{
            background: var(--panel);
            border:1px solid var(--line);
            border-radius:14px;
            box-shadow: 0 10px 24px rgba(0,0,0,.25);
        }
        .muted{ color:var(--muted); }
        .title{ font-weight:800; letter-spacing:.2px; }
        .btn-soft{
            border:1px solid rgba(122,162,255,.55);
            color:#dbe7ff;
            background: rgba(122,162,255,.16);
        }
        .btn-soft:hover{
            color:#fff;
            border-color:rgba(122,162,255,.85);
            background: rgba(122,162,255,.26);
        }
        .summary{
            display:grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap:12px;
        }
        .summary .box{
            padding:14px 16px;
            border:1px solid var(--line);
            border-radius:12px;
            background: rgba(255,255,255,.03);
        }
        .summary .num{ font-size:26px; font-weight:800; line-height:1; }
        .summary .label{ color:var(--muted); margin-top:4px; font-size:13px; }
        .grid{
            display:grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap:14px;
        }
        .chart-wrap{ min-height: 220px; }
        .table td,.table th{ color:var(--text); border-top-color: var(--line); }
        .table thead th{ color:#cbd8f7; border-bottom-color: var(--line); font-size:12px; letter-spacing:.4px; }
        .winner-row{ background: rgba(34,197,94,.14); }
        .tag-live{
            display:inline-flex; align-items:center; gap:6px;
            font-size:12px; color:#b7f5c8;
            border:1px solid rgba(34,197,94,.4);
            border-radius:999px;
            padding:4px 10px;
            background: rgba(34,197,94,.12);
        }
        .dot{
            width:8px; height:8px; border-radius:999px;
            background: var(--ok);
            box-shadow: 0 0 0 0 rgba(34,197,94,.8);
            animation: pulse 1.8s infinite;
        }
        @keyframes pulse{
            0%{ box-shadow:0 0 0 0 rgba(34,197,94,.75); }
            70%{ box-shadow:0 0 0 10px rgba(34,197,94,0); }
            100%{ box-shadow:0 0 0 0 rgba(34,197,94,0); }
        }
        @media (max-width: 992px){
            .grid{ grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 768px){
            .summary{ grid-template-columns: 1fr; }
            .grid{ grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card mb-3">
        <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
            <div>
                <div class="title h4 mb-1">{{ $evoting->judul }}</div>
                <div class="muted">{!! nl2br(e($evoting->deskripsi ?: 'Hasil perolehan suara e-voting.')) !!}</div>
            </div>
            <div class="mt-3 mt-md-0 d-flex align-items-center">
                <span class="tag-live mr-2"><span class="dot"></span>Realtime</span>
                <a href="{{ route('evoting.public', $token) }}" class="btn btn-sm btn-soft">Halaman Voting</a>
            </div>
        </div>
    </div>

    <div class="summary mb-3">
        <div class="box">
            <div class="num" id="sumTotal">0</div>
            <div class="label">Total Peserta</div>
        </div>
        <div class="box">
            <div class="num" id="sumVoted">0</div>
            <div class="label">Sudah Voting</div>
        </div>
        <div class="box">
            <div class="num" id="sumPending">0</div>
            <div class="label">Belum Voting</div>
        </div>
    </div>

    <div class="grid" id="resultGrid"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (function(){
        var dataUrl = @json(route('evoting.public.results.data', $token));
        var grid = document.getElementById('resultGrid');
        var charts = {};
        var cards = {};

        function escapeHtml(str){
            return String(str || '').replace(/[&<>"']/g, function(m){
                return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[m];
            });
        }

        function renderCard(item){
            var id = 'item-' + item.id;
            if (cards[id]) return cards[id];

            var card = document.createElement('div');
            card.className = 'card';
            card.innerHTML = ''
                + '<div class="card-header d-flex justify-content-between align-items-center">'
                + '  <strong>' + escapeHtml(item.title) + '</strong>'
                + '  <span class="muted">Total suara: <span data-item-total="' + item.id + '">0</span></span>'
                + '</div>'
                + '<div class="card-body">'
                + '  <div class="chart-wrap mb-3"><canvas id="chart-' + item.id + '" height="180"></canvas></div>'
                + '  <div class="table-responsive">'
                + '    <table class="table table-sm mb-0">'
                + '      <thead><tr><th>Kandidat</th><th class="text-center">Suara</th><th class="text-center">Persentase</th></tr></thead>'
                + '      <tbody data-body="' + item.id + '"></tbody>'
                + '    </table>'
                + '  </div>'
                + '</div>';

            grid.appendChild(card);
            cards[id] = card;
            return card;
        }

        function upsertRows(item){
            var body = document.querySelector('[data-body="' + item.id + '"]');
            if (!body) return;
            var max = 0;
            item.candidates.forEach(function(c){ if ((c.total || 0) > max) max = c.total || 0; });
            body.innerHTML = item.candidates.map(function(c){
                var winner = (max > 0 && c.total === max) ? 'winner-row' : '';
                return ''
                    + '<tr class="' + winner + '">'
                    + '  <td>' + escapeHtml(c.name) + '</td>'
                    + '  <td class="text-center">' + (c.total || 0) + '</td>'
                    + '  <td class="text-center">' + (c.percent || 0) + '%</td>'
                    + '</tr>';
            }).join('');
        }

        function upsertChart(item){
            var labels = item.candidates.map(function(c){ return c.name; });
            var values = item.candidates.map(function(c){ return c.total || 0; });
            var max = 0;
            values.forEach(function(v){ if (v > max) max = v; });
            var colors = values.map(function(v){
                return (max > 0 && v === max) ? 'rgba(34,197,94,.85)' : 'rgba(79,70,229,.6)';
            });

            var chartId = 'chart-' + item.id;
            if (!charts[chartId]) {
                var canvas = document.getElementById(chartId);
                charts[chartId] = new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: values,
                            backgroundColor: colors,
                            borderColor: colors,
                            borderWidth: 1,
                            borderRadius: 8,
                            barThickness: 14
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        plugins: { legend: { display: false } },
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: { precision: 0, color: '#e8eefb' },
                                grid: { color: 'rgba(255,255,255,.08)' }
                            },
                            y: {
                                ticks: { color: '#e8eefb' },
                                grid: { color: 'rgba(255,255,255,.08)' }
                            }
                        },
                        animation: { duration: 700, easing: 'easeOutQuart' }
                    }
                });
            } else {
                charts[chartId].data.labels = labels;
                charts[chartId].data.datasets[0].data = values;
                charts[chartId].data.datasets[0].backgroundColor = colors;
                charts[chartId].data.datasets[0].borderColor = colors;
                charts[chartId].update();
            }
        }

        function updateSummary(voters){
            var total = (voters || []).length;
            var voted = (voters || []).filter(function(v){ return !!v.voted; }).length;
            var pending = Math.max(0, total - voted);
            document.getElementById('sumTotal').textContent = total;
            document.getElementById('sumVoted').textContent = voted;
            document.getElementById('sumPending').textContent = pending;
        }

        function updateView(payload){
            if (!payload || !Array.isArray(payload.items)) return;
            updateSummary(payload.voters || []);

            payload.items.forEach(function(item){
                renderCard(item);
                upsertRows(item);
                upsertChart(item);
                var totalEl = document.querySelector('[data-item-total="' + item.id + '"]');
                if (totalEl) totalEl.textContent = String(item.total || 0);
            });
        }

        function fetchData(){
            fetch(dataUrl, { headers: { 'Accept': 'application/json' } })
                .then(function(res){ return res.json(); })
                .then(updateView)
                .catch(function(){});
        }

        fetchData();
        setInterval(fetchData, 5000);
    })();
</script>
</body>
</html>
