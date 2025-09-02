<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Undangan Rapat</title>
    <style>
        @page {
            size: A4 portrait;
            margin-top: 2cm;
            margin-bottom: 2cm;
            margin-left: 2.5cm;
            margin-right: 2.5cm;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            margin: 0;
        }
        .kop {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 6px;
            margin-bottom: 18px;
        }
        .judul { text-align: center; font-size: 14pt; font-weight: bold; margin: 24px 0 16px 0;}
        .ttd { float:right; width:260px; text-align:left;}
        .alamat { font-size: 10pt; }
        ol { margin-top: 0; }
        table { width: 100%; }
    </style>
</head>
<body>
    <!-- Kop Surat Gambar -->
    <div class="kop">
        <img src="{{ $kop_path }}" style="width: 100%; height: auto;">
    </div>

    <table style="margin-bottom: 16px;">
        <tr>
            <td width="17%">Nomor</td>
            <td width="3%">:</td>
            <td>{{ $rapat->nomor_undangan }}</td>
            <td style="text-align:right;" width="40%">Manokwari, {{ \Carbon\Carbon::parse($rapat->tanggal)->isoFormat('D MMMM Y') }}</td>
        </tr>
        <tr>
            <td>Lampiran</td>
            <td>:</td>
            <td>Satu Lembar</td>
        </tr>
        <tr>
            <td>Hal</td>
            <td>:</td>
            <td>Undangan {{ $rapat->judul }}</td>
        </tr>
    </table>

    <p>Kepada Yth. <br>
    Para Pejabat pada daftar terlampir. <br>
    <br>
    di<br>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;tempat
    </p>

    <p>Assalamu’alaikum Wr.Wb.</p>
    <p>
        Memohon kehadiran Bapak/Ibu/Saudara dalam <b>{{ $rapat->judul }}</b>, yang akan dilaksanakan pada:
    </p>
    <table style="margin-left:30px; margin-bottom:10px;">
        <tr>
            <td width="120">Hari, Tanggal</td>
            <td>:</td>
            <td>{{ \Carbon\Carbon::parse($rapat->tanggal)->isoFormat('dddd, D MMMM Y') }}</td>
        </tr>
        <tr>
            <td>Waktu</td>
            <td>:</td>
            <td>{{ $rapat->waktu_mulai }} WIT s/d Selesai</td>
        </tr>
        <tr>
            <td>Tempat</td>
            <td>:</td>
            <td>{{ $rapat->tempat }}</td>
        </tr>
        <tr>
            <td>Agenda</td>
            <td>:</td>
            <td>{{ $rapat->deskripsi ?? $rapat->judul }}</td>
        </tr>
    </table>
    <p>
        Demikian, atas perhatiannya diucapkan terima kasih.<br>
        Wassalamu’alaikum Wr.Wb.
    </p>
    <br><br>
    <div class="ttd ">
        {{ $pimpinan->jabatan ?? '-' }},<br><br><br><br>
        <b>{{ $pimpinan->nama ?? '-' }}</b>
    </div>
    <div style="page-break-after: always;"></div>
    @include('rapat.lampiran_pdf', ['rapat' => $rapat, 'daftar_peserta' => $daftar_peserta, 'pimpinan' => $pimpinan])
</body>
</html>
