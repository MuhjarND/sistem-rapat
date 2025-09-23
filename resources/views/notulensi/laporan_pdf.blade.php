{{-- resources/views/notulensi/laporan_pdf.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Notulensi</title>
    <style>
        @page { size: A4 portrait; margin: 2cm 2.5cm; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; margin: 0; color:#000; }
        .kop img { width: 100%; height: auto; }
        h1 { font-size: 16pt; text-align: center; margin: 6px 0 10px; }
        table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 3px 0; vertical-align: top; }
        .sep { height: 10px; }
        .tb { border: 1px solid #000; }
        .tb th, .tb td { border: 1px solid #000; padding: 6px; }
        .center { text-align: center; }
        .right  { text-align: right; }
        .sign-wrap { display: table; width:100%; margin-top: 12px; }
        .sign-col { display: table-cell; width: 50%; vertical-align: top; padding-left: 16px; }
        .sign-title { margin-bottom: 6px; }
        .qr { width: 120px; height: auto; margin: 6px 0; }
        .qr-note { font-size: 9.5pt; color: #444; line-height: 1.35; }
        .mt-6 { margin-top: 6px; }
        .mt-12 { margin-top: 12px; }
        .small { font-size: 10pt; color:#222; }
        .muted { color:#666; }
        .footnote { font-size: 9pt; color:#555; margin-top: 10px; }
        .break { page-break-after: always; }
    </style>
</head>
<body>

    {{-- KOP SURAT --}}
    <div class="kop">
        {{-- $kop harus berupa path absolut filesystem dari controller: public_path('kop_notulen.jpg') --}}
        @if(!empty($kop))
            <img src="{{ $kop }}">
        @endif
    </div>

    <h1>NOTULENSI RAPAT</h1>

    {{-- META RAPAT --}}
    <table class="meta">
        <tr>
            <td width="22%">Nomor</td>
            <td width="2%">:</td>
            <td>{{ $rapat->nomor_undangan ?? '-' }}</td>
            <td class="right" width="35%">
                {{ \Carbon\Carbon::parse($rapat->tanggal)->isoFormat('D MMMM Y') }}
            </td>
        </tr>
        <tr>
            <td>Jenis Kegiatan</td>
            <td>:</td>
            <td colspan="2">{{ $rapat->nama_kategori ?? '-' }}</td>
        </tr>
        <tr>
            <td>Nama Kegiatan</td>
            <td>:</td>
            <td colspan="2"><b>{{ $rapat->judul }}</b></td>
        </tr>
        <tr>
            <td>Hari/Tanggal</td>
            <td>:</td>
            <td colspan="2">{{ \Carbon\Carbon::parse($rapat->tanggal)->isoFormat('dddd, D MMMM Y') }}</td>
        </tr>
        <tr>
            <td>Waktu</td>
            <td>:</td>
            <td colspan="2">{{ $rapat->waktu_mulai }} WIT s/d selesai</td>
        </tr>
        <tr>
            <td>Tempat</td>
            <td>:</td>
            <td colspan="2">{{ $rapat->tempat }}</td>
        </tr>
    </table>

    <div class="sep"></div>

    {{-- DAFTAR PESERTA (opsional) --}}
    @if(!empty($peserta) && count($peserta) > 0)
        <table class="tb">
            <thead>
                <tr class="center">
                    <th width="6%">No</th>
                    <th>Nama</th>
                    <th width="35%">Jabatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($peserta as $i => $p)
                    <tr>
                        <td class="center">{{ $i + 1 }}</td>
                        <td>{{ $p->name }}</td>
                        <td>{{ $p->jabatan ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="sep"></div>
    @endif

    {{-- BLOK TANDA TANGAN / QR --}}
    <div class="sign-wrap">
        {{-- Kolom 1: Notulis (selalu ada QR) --}}
        <div class="sign-col">
            <div class="sign-title">{{ $notulis_jabatan ?? 'Notulis' }},</div>

            @if(!empty($qr_notulis_data))
                <img class="qr" src="{{ $qr_notulis_data }}">
                <div class="qr-note">
                    Terverifikasi digital — QR ini <b>KHUSUS untuk dokumen NOTULENSI</b> sebagai TTD <b>Notulis</b>.<br>
                    Scan akan membuka halaman verifikasi & validasi HMAC.
                </div>
            @else
                <div class="small muted mt-6"><i>QR Notulis belum tersedia.</i></div>
            @endif

            <div class="mt-12"><b>{{ $notulis_nama ?? '-' }}</b></div>
        </div>

        {{-- Kolom 2: Pimpinan Rapat (QR muncul setelah approval selesai) --}}
        <div class="sign-col">
            <div class="sign-title">{{ $pimpinan_jabatan ?? 'Pimpinan Rapat' }},</div>

            @if(!empty($qr_pimpinan_data))
                <img class="qr" src="{{ $qr_pimpinan_data }}">
                <div class="qr-note">
                    Terverifikasi digital — TTD <b>Pimpinan Rapat</b> untuk dokumen NOTULENSI.<br>
                    Muncul setelah semua approval notulensi disetujui.
                </div>
            @else
                <div class="small muted mt-6"><i>MENUNGGU APPROVAL PIMPINAN — QR akan tampil otomatis setelah disetujui.</i></div>
            @endif

            <div class="mt-12"><b>{{ $pimpinan_nama ?? '-' }}</b></div>
        </div>
    </div>

    {{-- CATATAN VERIFIKASI --}}
    <div class="footnote">
        <b>Catatan:</b> Keaslian dokumen dapat diverifikasi dengan memindai salah satu QR di atas.
        Sistem akan memeriksa integritas payload & keterkaitan approval (HMAC).
    </div>

</body>
</html>
