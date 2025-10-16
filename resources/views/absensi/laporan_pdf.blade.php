<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Absensi</title>
    <style>
        @page { size: A4 portrait; margin: 2cm 2.5cm; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; margin:0; }
        table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 2px 0; vertical-align: top; }

        .tb { border:1px solid #000; }
        .tb th, .tb td { border:1px solid #000; padding:6px; }
        .center { text-align: center; }

        .ttd { float:right; width:260px; text-align:left; }
        .qr-note { font-size: 10pt; color:#444; line-height:1.35; }
        .clearfix::after { content:""; display:table; clear:both; }

        .ttd-col { text-align:center; }
        .ttd-box { height: 56px; display:flex; align-items:center; justify-content:center; }
        .ttd-img { display:block; margin:0 auto; max-height:56px; max-width:140px; height:auto; width:auto; }
        .ttd-empty { width:120px; height:48px; border:1px dashed #777; margin:0 auto; }
        .muted { color:#666; font-size: 10pt; }
    </style>
</head>
<body>
    @if(!empty($kop) && @file_exists($kop))
        <div><img src="{{ $kop }}" style="width:100%; height:auto;"></div>
        <br>
    @endif

    {{-- Meta rapat --}}
    <table class="meta" style="margin-bottom: 10px;">
        <tr><td width="22%">Jenis Kegiatan</td><td width="2%">:</td><td>{{ $rap['nama_kategori'] }}</td></tr>
        <tr><td>Nama Kegiatan</td><td>:</td><td>{{ $rap['judul'] }}</td></tr>
        <tr><td>Hari/Tanggal</td><td>:</td><td>{{ $rap['tanggal_human'] }}</td></tr>
        <tr><td>Waktu</td><td>:</td><td>{{ $rap['waktu_mulai'] ? $rap['waktu_mulai'].' WIT s/d selesai' : '-' }}</td></tr>
        <tr><td>Tempat</td><td>:</td><td>{{ $rap['tempat'] }}</td></tr>
    </table>
    <br>

    {{-- Tabel peserta --}}
    <table class="tb">
        <thead>
            <tr class="center">
                <th width="6%">No</th>
                <th>Nama</th>
                <th width="28%">Jabatan</th>
                <th width="26%">Tanda Tangan</th>
                <th width="10%">Ket</th>
            </tr>
        </thead>
        <tbody>
            @forelse($peserta as $i => $p)
                <tr>
                    <td class="center">{{ $i+1 }}</td>
                    <td><b>{{ $p['name'] }}</b></td>
                    <td>{{ $p['jabatan'] ?: '-' }}</td>
                    <td class="ttd-col">
                        <div class="ttd-box">
                            @if(!empty($p['ttd_data']))
                                <img class="ttd-img" src="{{ $p['ttd_data'] }}" alt="TTD {{ $p['name'] }}">
                            @else
                                <div class="ttd-empty"></div>
                            @endif
                        </div>
                        @if(!empty($p['waktu_absen']))
                            <div class="muted">{{ $p['waktu_absen'] }}</div>
                        @endif
                    </td>
                    <td class="center">{{ $p['status'] ? strtoupper($p['status']) : '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="center">Tidak ada data peserta.</td></tr>
            @endforelse
        </tbody>
    </table>
    <br><br>

    {{-- Blok TTD/QR --}}
    <div class="clearfix">
        <table class="ttd">
            <tr>
                <td>
                    <div>{{ $approver['jabatan'] ?: 'Penanggung Jawab' }},</div>

                    @if(!empty($qrSrc))
                        <img src="{{ $qrSrc }}" style="width:130px; height:auto; margin:8px 0;">
                        <div class="qr-note">Terverifikasi digital (Melalui Aplikasi Sistem Rapat)</div>
                    @else
                        <div class="qr-note" style="margin:8px 0;">
                            <i>MENUNGGU APPROVAL ABSENSI</i><br>
                            QR absensi akan muncul otomatis setelah seluruh approval ABSENSI selesai.
                        </div>
                    @endif

                    <b>{{ $approver['nama'] ?: '-' }}</b>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
    