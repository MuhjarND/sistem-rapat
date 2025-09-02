<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Absensi</title>
    <style>
        @page { size: A4 portrait; margin: 2cm 2.5cm; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; margin:0; }
        .kop { text-align: center; border-bottom: 2px solid #000; margin-bottom: 12px; padding-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 2px 0; vertical-align: top; }
        .tb { border:1px solid #000; }
        .tb th, .tb td { border:1px solid #000; padding:6px; }
        .center { text-align: center; }
        .ttd { float:right; width:260px; text-align:left;}
    </style>
</head>
<body>
    <div>
        <img src="{{ $kop }}" style="width:100%; height:auto;">
    </div><br>

    <table class="meta" style="margin-bottom: 10px;">
        <tr><td width="22%">Jenis Kegiatan</td><td width="2%">:</td><td>{{ $rapat->nama_kategori ?? '-' }}</td></tr>
        <tr><td>Nama Kegiatan</td><td>:</td><td>{{ $rapat->judul }}</td></tr>
        <tr><td>Hari/Tanggal</td><td>:</td><td>{{ \Carbon\Carbon::parse($rapat->tanggal)->isoFormat('dddd, D MMMM Y') }}</td></tr>
        <tr><td>Waktu</td><td>:</td><td>{{ $rapat->waktu_mulai }} WIT s/d selesai</td></tr>
        <tr><td>Tempat</td><td>:</td><td>{{ $rapat->tempat }}</td></tr>
    </table><br>

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
                <td>{{ $p->name }}</td>
                <td>{{ $p->jabatan ?? '-' }}</td>
                <td><!-- kolom tanda tangan manual saat cetak --></td>
                <td class="center">{{ strtoupper($p->status ?? '') }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="center">Tidak ada data peserta.</td></tr>
            @endforelse
        </tbody>
    </table><br><br>

    <table class="ttd">
        <tr>
            <td>
                {{ $rapat->jabatan_pimpinan ?? 'Penanggung Jawab' }},<br><br><br><br><br>
                <b>{{ $rapat->nama_pimpinan ?? '-' }}</b>
            </td>
        </tr>
    </table>
</body>
</html>
