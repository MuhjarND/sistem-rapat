﻿@php
    if (!isset($tampilkan_lampiran)) {
        $tampilkan_lampiran = (isset($daftar_peserta) && method_exists($daftar_peserta, 'count'))
            ? ($daftar_peserta->count() > 5)
            : false;
    }
    if (!isset($tampilkan_daftar_di_surat)) {
        $tampilkan_daftar_di_surat = !$tampilkan_lampiran;
    }
    $kategoriNamaDisplay = trim((string) ($rapat->nama_kategori ?? $rapat->kategori_nama ?? $rapat->kategori ?? ''));
    if ($kategoriNamaDisplay === '-') {
        $kategoriNamaDisplay = '';
    }
    $kategoriNama = strtolower($kategoriNamaDisplay);
    $showPakaian = (int)($rapat->kategori_butuh_pakaian ?? 0) === 1;
    if (!$showPakaian) {
        // Fallback data lama jika kolom kategori_butuh_pakaian belum tersedia.
        $showPakaian = in_array($kategoriNama, [
            strtolower('Penandatanganan Pakta Integritas dan Komitmen Bersama'),
            strtolower('Buka Puasa Bersama'),
        ], true);
    }
    $isVirtual = !empty($rapat->is_virtual);
    $meetingId = trim((string) ($rapat->meeting_id ?? ''));
    $meetingPasscode = trim((string) ($rapat->meeting_passcode ?? ''));
    $detailTambahan = trim((string) ($rapat->detail_tambahan ?? ''));
    $detailTambahanInline = trim((string) preg_replace('/\s+/', ' ', $detailTambahan));
    if ($detailTambahanInline !== '') {
        // Rapikan tanda baca agar alur kalimat undangan tidak patah/berantakan.
        $detailTambahanInline = preg_replace('/\s+:\s*/', ': ', $detailTambahanInline);
        $detailTambahanInline = preg_replace('/\s*,\s*/', ', ', $detailTambahanInline);
    }
    $tujuanSurat = trim((string) ($rapat->tujuan_surat ?? ''));
    if ($tujuanSurat !== '') {
        $tujuanSurat = preg_replace('/\r\n|\r/', "\n", $tujuanSurat);
        $tujuanSurat = preg_replace('/(Ketua Pengadilan Agama)\s+/iu', "$1\n", $tujuanSurat, 1);
        $tampilkan_lampiran = false;
        $tampilkan_daftar_di_surat = false;
    }
    $jumlahUndangan = isset($daftar_peserta)
        ? (method_exists($daftar_peserta, 'count') ? (int) $daftar_peserta->count() : count((array) $daftar_peserta))
        : 0;
    $penerimaTunggalNama = null;
    if ($jumlahUndangan === 1 && isset($daftar_peserta)) {
        $first = method_exists($daftar_peserta, 'first') ? $daftar_peserta->first() : (is_array($daftar_peserta) ? ($daftar_peserta[0] ?? null) : null);
        $penerimaTunggalNama = \App\Helpers\NameHelper::withoutTitles($first->name ?? '');
    }
    $approval1Nama = \App\Helpers\NameHelper::withoutTitles($approval1->name ?? '-');
    $approval1Jabatan = data_get($rapat, 'approval1_jabatan_manual') ?: ($approval1->jabatan ?? 'Approval 1');
    $isKetua = stripos((string) $approval1Jabatan, 'ketua') !== false;
@endphp

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
        .ttd { float:right; width:300px; text-align:left; }
        .ttd2 { float:right; width:300px; text-align:left; margin-top: 18px; }
        .alamat { font-size: 10pt; }
        table { width: 100%; }
        ol { margin: 6px 0 8px 18px; padding: 0; }
        .clearfix::after { content: ""; display: table; clear: both; }

        /* QR block */
        .qr { height: 90px; }
        .qr-caption { font-size: 10pt; color:#333; }
        .qr-placeholder { height: 90px; }
        .waiting-note { font-size:10pt; color:#666; margin-top:4px; display:inline-block; }
        .isi-undangan {
            margin: 0 0 10px 0;
            text-align: justify;
            text-justify: inter-word;
            line-height: 1.45;
            text-indent: 32px;
        }
        .penutup-undangan {
            margin: 0;
            text-align: justify;
            text-justify: inter-word;
            line-height: 1.45;
            text-indent: 32px;
        }
    </style>
</head>
<body>
    <!-- Kop Surat Gambar -->
    <div class="kop">
        <img src="{{ $kop_path }}" style="width: 100%; height: auto;">
    </div>

    <table style="margin-bottom: 16px; border-collapse:collapse; width:100%;">
        <tr>
            <td width="14%">Nomor</td>
            <td width="2%">:</td>
            <td width="44%">{{ $rapat->nomor_undangan }}</td>
            <td style="text-align:right; font-size:11px;" width="40%">
                Manokwari, {{ \Carbon\Carbon::parse($rapat->tanggal)->isoFormat('D MMMM Y') }}
            </td>
        </tr>
        <tr>
            <td>Lampiran</td>
            <td>:</td>
            <td>{{ $tampilkan_lampiran ? 'Satu Lembar' : '-' }}</td>
            <td></td>
        </tr>
        <tr>
            <td>Hal</td>
            <td>:</td>
            <td>Undangan{{ $kategoriNamaDisplay !== '' ? ' '.$kategoriNamaDisplay : '' }}</td>
            <td></td>
        </tr>
    </table>

    {{-- Kepada Yth + daftar peserta (jika <= 5) --}}
    @if($tujuanSurat !== '')
        <p style="margin-bottom: 2px;">Kepada Yth.</p>
        <p style="margin: 0 0 6px 0;">{!! nl2br(e($tujuanSurat)) !!}</p>
    @elseif($jumlahUndangan === 1 && !empty($penerimaTunggalNama))
        @php
            $singlePenerimaEscaped = e($penerimaTunggalNama);
            $singlePenerimaFormatted = preg_replace('/(Ketua Pengadilan Agama)\s+/iu', '$1<br>', $singlePenerimaEscaped, 1);
        @endphp
        <p style="margin-bottom: 2px;">Kepada Yth.</p>
        <p style="margin: 0 0 6px 0;">{!! $singlePenerimaFormatted !!}</p>
    @else
        <p style="margin-bottom: 6px;">Kepada Yth. Para Pejabat dan Pegawai (terlampir)</p>
    @endif

    @if($tampilkan_daftar_di_surat && $jumlahUndangan > 1)
        @php
            $jabatanList = collect($daftar_peserta ?? [])
                ->map(function($p){ return trim($p->jabatan ?? ''); })
                ->filter(function($v){ return $v !== ''; })
                ->unique()
                ->values();
        @endphp
        @if($jabatanList->count())
            <ol>
                @foreach($jabatanList as $jab)
                    @php
                        $jabEscaped = e($jab);
                        $jabFormatted = preg_replace('/(Ketua Pengadilan Agama)\s+/iu', '$1<br>', $jabEscaped, 1);
                    @endphp
                    <li>{!! $jabFormatted !!}</li>
                @endforeach
            </ol>
        @else
            <div class="text-muted">-</div>
        @endif
    @endif

    <p style="margin-top: 0;">
        di<br>
        Tempat
    </p>

   <p><i>Assalamu'alaikum Wr.Wb.</i></p>
    @if($detailTambahanInline !== '')
        <p class="isi-undangan">
            {{ $detailTambahanInline }} <b>{{ $rapat->judul }}</b>, yang akan dilaksanakan pada:
        </p>
    @else
        <p class="isi-undangan">
            Memohon kehadiran Bapak/Ibu/Saudara dalam <b>{{ $rapat->judul }}</b>, yang akan dilaksanakan pada:
        </p>
    @endif

    <table style="margin-left:30px; margin-bottom:10px;">
        <tr>
            <td width="120">Hari, Tanggal</td>
            <td>:</td>
            <td>{{ \Carbon\Carbon::parse($rapat->tanggal)->isoFormat('dddd, D MMMM Y') }}</td>
        </tr>
        <tr>
            <td>Waktu</td>
            <td>:</td>
            <td>{{ \App\Helpers\TimeHelper::short($rapat->waktu_mulai) }} WIT s/d Selesai</td>
        </tr>
        <tr>
            <td>Tempat</td>
            <td>:</td>
            <td>{{ $rapat->tempat }}</td>
        </tr>
        @if($isVirtual && ($meetingId !== '' || $meetingPasscode !== ''))
        <tr>
            <td>Meeting ID</td>
            <td>:</td>
            <td>{{ $meetingId !== '' ? $meetingId : '-' }}</td>
        </tr>
        <tr>
            <td>Passcode</td>
            <td>:</td>
            <td>{{ $meetingPasscode !== '' ? $meetingPasscode : '-' }}</td>
        </tr>
        @endif
        <tr>
            <td>Agenda</td>
            <td>:</td>
            <td>{{ $rapat->deskripsi ?? $rapat->judul }}</td>
        </tr>
        @if($showPakaian)
        <tr>
            <td>Pakaian</td>
            <td>:</td>
            <td>{{ $rapat->jenis_pakaian ?: '-' }}</td>
        </tr>
        @endif
    </table>

    <p class="penutup-undangan">
        Demikian, atas perhatiannya diucapkan terima kasih.<br><br>
        <i>Wassalamu'alaikum Wr.Wb.</i>
    </p>
    <br><br>

    <div class="clearfix">
        {{-- Tanda tangan Approval 1 (wajib) --}}
        <div class="ttd">
            <b>{{ $approval1Jabatan }},</b><br>
            @if(!empty($approval1->unit))
            <b><span style="font-size:16px;">{{ $approval1->unit }}</span><br></b>
            @endif
            @php
                $qrA1Exists = !empty($qrA1) && file_exists(public_path($qrA1));
            @endphp

            @if($qrA1Exists)
                {{-- Tampilkan QR jika sudah approved --}}
                <img class="qr" src="{{ public_path($qrA1) }}"><br>
                <span class="qr-caption">Terverifikasi digital (Melalui SMART)</span><br>
            @else
                {{-- Jika belum approved, tampilkan placeholder + teks menunggu --}}
                <div class="qr-placeholder"></div><br>
                <span class="waiting-note"><em>Menunggu approval</em></span><br>
            @endif

            <b>{{ $approval1Nama }}</b>
        </div>
    </div>

    @if(!$isKetua)
        <div style="margin-top:16px;">
            <b>Tembusan:</b><br> Yth. Ketua Pengadilan Tinggi Agama Papua Barat (sebagai laporan)
        </div>
    @endif

    {{-- Lampiran HANYA bila peserta > 5 --}}
    @if($tampilkan_lampiran)
        <div style="page-break-after: always;"></div>
        @include('rapat.lampiran_pdf', [
            'rapat'          => $rapat,
            'daftar_peserta' => $daftar_peserta,
            'approval1'      => $approval1,
            'approval2'      => $approval2,
            'qrA1'           => $qrA1,
            'qrA2'           => $qrA2,
        ])
    @endif
</body>
</html>


