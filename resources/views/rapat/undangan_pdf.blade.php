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
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; margin: 0; }
        .kop { text-align: center; border-bottom: 3px double #000; padding-bottom: 6px; margin-bottom: 18px; }
        .judul { text-align: center; font-size: 14pt; font-weight: bold; margin: 24px 0 16px 0;}
        .ttd { float:right; width:260px; text-align:left; }
        .ttd2 { float:right; width:260px; text-align:left; margin-top: 18px; }
        .alamat { font-size: 10pt; }
        table { width: 100%; }
        ol { margin: 6px 0 8px 18px; padding: 0; }
        .clearfix::after { content: ""; display: table; clear: both; }
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
            <td style="text-align:right;" width="40%">
                Manokwari, {{ \Carbon\Carbon::parse($rapat->tanggal)->isoFormat('D MMMM Y') }}
            </td>
        </tr>
        <tr>
            <td>Lampiran</td>
            <td>:</td>
            <td>{{ $tampilkan_lampiran ? 'Satu Lembar' : '-' }}</td>
        </tr>
        <tr>
            <td>Hal</td>
            <td>:</td>
            <td>Undangan {{ $rapat->judul }}</td>
        </tr>
    </table>

    {{-- Kepada Yth + daftar peserta (jika ≤ 5) --}}
    <p style="margin-bottom: 6px;">Kepada Yth.</p>

    @if($tampilkan_daftar_di_surat)
        <ol>
            @foreach($daftar_peserta as $p)
                <li>{{ $p->jabatan ? $p->jabatan.' - ' : '' }}{{ $p->name }}</li>
            @endforeach
        </ol>
    @endif

    <p style="margin-top: 0;">
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

    <div class="clearfix">
        {{-- Tanda tangan Approval 1 (wajib) --}}
        <div class="ttd">
            {{ $approval1->jabatan ?? 'Approval 1' }},<br><br><br><br>
            <b>{{ $approval1->name ?? '-' }}</b>
        </div>

        {{-- Tanda tangan Approval 2 (opsional) --}}
        @if(!empty($approval2))
        <div class="ttd2">
            {{ $approval2->jabatan ?? 'Approval 2' }},<br><br><br><br>
            <b>{{ $approval2->name }}</b>
        </div>
        @endif
    </div>

    {{-- Lampiran HANYA bila peserta > 5 --}}
    @if($tampilkan_lampiran)
        <div style="page-break-after: always;"></div>
        @include('rapat.lampiran_pdf', [
            'rapat' => $rapat,
            'daftar_peserta' => $daftar_peserta,
            'approval1' => $approval1,
            'approval2' => $approval2
        ])
    @endif
</body>
</html>
