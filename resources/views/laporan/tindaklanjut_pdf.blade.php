<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Tindak Lanjut</title>
<style>
    @page { size: A4 landscape; margin: 18mm 16mm 18mm 16mm; }
    body { font-family: "Times New Roman", serif; font-size: 11pt; margin: 0; }
    h2 { margin: 0 0 8px 0; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border:1px solid #000; padding:6px 8px; vertical-align: top; }
    th { background:#eee; }
    .kop{ text-align:center; margin-bottom:14px; }
    .ttd-box{ margin-top:20px; text-align:right; }
</style>
</head>
<body>
    @php $kopPath = public_path('kop_laporan.jpg'); @endphp
    @if(is_file($kopPath))
        <div class="kop">
            <img src="{{ $kopPath }}" style="width:100%; height:auto;">
        </div>
    @endif

    <h2 style="text-align:center;">Laporan Tindak Lanjut</h2>
    <table style="margin-bottom:12px; width:100%; border:0;">
        <tr><td style="border:0; width:160px;"><strong>Rapat</strong></td><td style="border:0;">: {{ $rapat->judul ?? '-' }}</td></tr>
        <tr><td style="border:0;"><strong>Tanggal</strong></td><td style="border:0;">: {{ $rapat->tanggal ?? '-' }}</td></tr>
        <tr><td style="border:0;"><strong>Waktu</strong></td><td style="border:0;">: {{ \App\Helpers\TimeHelper::short($rapat->waktu_mulai ?? null) }} WIT</td></tr>
        <tr><td style="border:0;"><strong>Tempat</strong></td><td style="border:0;">: {{ $rapat->tempat ?? '-' }}</td></tr>
    </table>
    <table>
        <thead>
            <tr>
                <th style="width:40px;">No</th>
                <th style="width:260px;">Rekomendasi</th>
                <th style="width:260px;">Tindak Lanjut</th>
                <th style="width:120px;">Eviden (Barcode)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tasks as $i => $t)
                @php
                    $rekom = trim(preg_replace('/\s+/', ' ', str_replace('&nbsp;',' ', strip_tags($t->rekomendasi ?? '-'))));
                    $tindak = trim(preg_replace('/\s+/', ' ', str_replace('&nbsp;',' ', strip_tags($t->eviden_note ?? '-'))));
                @endphp
                <tr>
                    <td align="center">{{ $i+1 }}</td>
                    <td>{{ $rekom ?: '-' }}</td>
                    <td>{{ $tindak ?: '-' }}</td>
                    <td>
                        @php
                            $qrTarget = $t->eviden_link ?: ($t->eviden_path ? asset($t->eviden_path) : null);
                            $qrBase64 = null;
                            if ($qrTarget && class_exists('\\SimpleSoftwareIO\\QrCode\\Facades\\QrCode')) {
                                try {
                                    $qrBase64 = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(120)->margin(0)->generate($qrTarget));
                                } catch (\Throwable $e) { $qrBase64 = null; }
                            }
                        @endphp
                        @if($qrBase64)
                            <img src="data:image/png;base64,{{ $qrBase64 }}" alt="QR Eviden" style="width:110px;height:110px;">
                        @elseif($qrTarget)
                            <div style="word-break:break-all; font-size:9pt;">{{ $qrTarget }}</div>
                        @else
                            Belum ada eviden
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" align="center">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if(!empty($approval))
    <div style="margin-top:50px; width:100%;">
        <div style="width:280px; margin-left:auto; text-align:left;">
            <div style="font-weight:bold;">{{ $approval->jabatan ?? 'Approval' }}</div>
            @if(!empty($approval->unit))<div style="font-weight:bold;">{{ $approval->unit }}</div>@endif
            <div style="height:80px;"></div>
            <div style="font-weight:bold;">{{ $approval->name ?? '-' }}</div>
        </div>
    </div>
    @endif
</body>
</html>
