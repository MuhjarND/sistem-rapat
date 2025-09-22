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
    </style>
</head>
<body>
    {{-- Kop (kirim sebagai filesystem path dari controller, mis: public_path("kop_absen.jpg")) --}}
    <div>
        <img src="{{ $kop }}" style="width:100%; height:auto;">
    </div>
    <br>

    {{-- Meta rapat --}}
    <table class="meta" style="margin-bottom: 10px;">
        <tr>
            <td width="22%">Jenis Kegiatan</td>
            <td width="2%">:</td>
            <td>{{ $rapat->nama_kategori ?? '-' }}</td>
        </tr>
        <tr>
            <td>Nama Kegiatan</td>
            <td>:</td>
            <td>{{ $rapat->judul }}</td>
        </tr>
        <tr>
            <td>Hari/Tanggal</td>
            <td>:</td>
            <td>{{ \Carbon\Carbon::parse($rapat->tanggal)->isoFormat('dddd, D MMMM Y') }}</td>
        </tr>
        <tr>
            <td>Waktu</td>
            <td>:</td>
            <td>{{ $rapat->waktu_mulai }} WIT s/d selesai</td>
        </tr>
        <tr>
            <td>Tempat</td>
            <td>:</td>
            <td>{{ $rapat->tempat }}</td>
        </tr>
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
                <td>{{ $p->name }}</td>
                <td>{{ $p->jabatan ?? '-' }}</td>
                <td><!-- kolom tanda tangan manual saat cetak --></td>
                <td class="center">{{ strtoupper($p->status ?? '') }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="center">Tidak ada data peserta.</td></tr>
            @endforelse
        </tbody>
    </table>
    <br><br>

    {{-- Blok tanda tangan / QR absensi --}}
    <div class="clearfix">
        <table class="ttd">
            <tr>
                <td>
                    <div>{{ $approver_final_jabatan ?? 'Penanggung Jawab' }},</div>

                    @if(!empty($absensi_qr_data))
                        {{-- PRIORITAS: data URI base64 agar DomPDF pasti render --}}
                        <img src="{{ $absensi_qr_data }}" style="width:80px; height:auto; margin:8px 0;">
                        <div class="qr-note">
                            Terverifikasi digital (Melalui Aplikasi Sistem Rapat)
                        </div>
                    @elseif(!empty($absensi_qr_fs) && file_exists($absensi_qr_fs))
                        {{-- Fallback: path absolut di filesystem --}}
                        <img src="{{ $absensi_qr_fs }}" style="width:130px; height:auto; margin:8px 0;">
                        <div class="qr-note">Terverifikasi digital — QR ABSENSI (file lokal).</div>
                    @elseif(!empty($absensi_qr_web))
                        {{-- Fallback terakhir: URL publik --}}
                        <img src="{{ $absensi_qr_web }}" style="width:130px; height:auto; margin:8px 0;">
                        <div class="qr-note">Terverifikasi digital — QR ABSENSI (URL).</div>
                    @else
                        <div class="qr-note" style="margin:8px 0;">
                            <i>MENUNGGU APPROVAL ABSENSI</i><br>
                            QR absensi akan muncul otomatis setelah seluruh approval ABSENSI selesai.
                        </div>
                    @endif
                    <b>{{ $approver_final_nama ?? '-' }}</b>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
