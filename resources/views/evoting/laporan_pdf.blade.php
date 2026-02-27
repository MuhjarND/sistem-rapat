<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan E-Voting</title>
    <style>
        @page { margin: 20mm 16mm 18mm 16mm; }
        body { font-family: "Times New Roman", Times, serif; font-size: 12px; color: #111; }
        .kop { margin-bottom: 10px; }
        .kop img { width: 100%; height: auto; }
        .title { text-align: center; font-size: 16px; font-weight: bold; margin: 6px 0 2px; }
        .subtitle { text-align: center; font-size: 12px; margin: 0 0 12px; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .meta td { padding: 3px 4px; vertical-align: top; }
        .meta .k { width: 130px; font-weight: bold; }
        .meta .s { width: 10px; }
        .summary { width: 100%; border-collapse: collapse; margin: 4px 0 12px; }
        .summary td { border: 1px solid #444; padding: 6px 8px; }
        .summary .label { width: 35%; font-weight: bold; background: #f2f2f2; }
        .section { margin-top: 10px; font-size: 13px; font-weight: bold; }
        .table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .table th, .table td { border: 1px solid #444; padding: 5px 6px; }
        .table th { background: #ececec; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .muted { color: #555; }
        .item-head { margin-top: 10px; font-weight: bold; }
        .winner { background: #e9f7ea; font-weight: bold; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 10px; font-size: 11px; }
        .badge-ok { background: #d1fae5; color: #065f46; }
        .badge-no { background: #fee2e2; color: #991b1b; }
        .preline { white-space: pre-line; }
    </style>
</head>
<body>
    @php
        $belumCount = max(0, (int) $totalVoters - (int) $votedCount);
    @endphp

    @if(!empty($kop) && @is_file($kop))
        <div class="kop">
            <img src="{{ $kop }}" alt="Kop">
        </div>
    @endif

    <div class="title">LAPORAN HASIL E-VOTING</div>
    <div class="subtitle">Sistem Rapat PTA Papua Barat</div>

    <table class="meta">
        <tr>
            <td class="k">Judul E-Voting</td><td class="s">:</td><td>{{ $evoting->judul }}</td>
        </tr>
        <tr>
            <td class="k">Deskripsi</td><td class="s">:</td>
            <td class="preline">{{ trim((string) ($evoting->deskripsi ?? '')) !== '' ? $evoting->deskripsi : 'Tanpa deskripsi' }}</td>
        </tr>
        <tr>
            <td class="k">Status</td><td class="s">:</td><td>{{ strtoupper((string) $evoting->status) }}</td>
        </tr>
        <tr>
            <td class="k">Dibuat Oleh</td><td class="s">:</td><td>{{ $creatorName ?: '-' }}</td>
        </tr>
        <tr>
            <td class="k">Waktu Cetak</td><td class="s">:</td><td>{{ \Carbon\Carbon::parse($generatedAt)->format('d/m/Y H:i') }} WIT</td>
        </tr>
        <tr>
            <td class="k">Link Voting Publik</td><td class="s">:</td><td>{{ $publicLink }}</td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td class="label">Total Peserta</td>
            <td>{{ $totalVoters }} orang</td>
            <td class="label">Sudah Voting</td>
            <td>{{ $votedCount }} orang</td>
        </tr>
        <tr>
            <td class="label">Belum Voting</td>
            <td>{{ $belumCount }} orang</td>
            <td class="label">Jumlah Item Voting</td>
            <td>{{ is_countable($items) ? count($items) : 0 }} item</td>
        </tr>
    </table>

    <div class="section">A. Rekap Perolehan Suara</div>
    @forelse($items as $idx => $item)
        @php
            $candidateRows = collect($item['candidates'] ?? []);
            $maxVote = (int) $candidateRows->max('total');
        @endphp
        <div class="item-head">{{ $idx + 1 }}. {{ $item['title'] ?? '-' }} <span class="muted">(Total suara: {{ (int) ($item['total'] ?? 0) }})</span></div>
        <table class="table">
            <thead>
                <tr>
                    <th class="text-center" style="width:36px;">No</th>
                    <th>Kandidat</th>
                    <th class="text-center" style="width:80px;">Suara</th>
                    <th class="text-center" style="width:90px;">Persentase</th>
                </tr>
            </thead>
            <tbody>
                @forelse($candidateRows as $i => $row)
                    @php
                        $isWinner = $maxVote > 0 && ((int) ($row['total'] ?? 0) === $maxVote);
                    @endphp
                    <tr class="{{ $isWinner ? 'winner' : '' }}">
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td>{{ $row['name'] ?? '-' }}</td>
                        <td class="text-center">{{ (int) ($row['total'] ?? 0) }}</td>
                        <td class="text-center">{{ number_format((float) ($row['percent'] ?? 0), 1) }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center muted">Belum ada kandidat.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @empty
        <table class="table">
            <tbody>
                <tr>
                    <td class="text-center muted">Belum ada item voting.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    <div class="section">B. Status Peserta Voting</div>
    <table class="table">
        <thead>
            <tr>
                <th class="text-center" style="width:36px;">No</th>
                <th>Nama Peserta</th>
                <th class="text-center" style="width:130px;">Status</th>
                <th class="text-center" style="width:150px;">Waktu Voting</th>
            </tr>
        </thead>
        <tbody>
            @forelse($voters as $i => $voter)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $voter['name'] ?? '-' }}</td>
                    <td class="text-center">
                        @if(!empty($voter['voted']))
                            <span class="badge badge-ok">Sudah</span>
                        @else
                            <span class="badge badge-no">Belum</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if(!empty($voter['voted_at']))
                            {{ \Carbon\Carbon::parse($voter['voted_at'])->format('d/m/Y H:i') }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center muted">Data peserta voting tidak tersedia.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
