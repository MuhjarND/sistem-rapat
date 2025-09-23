@php 
    use Carbon\Carbon;

    // (opsional) judul kolom tengah
    $kat = strtolower($rapat->nama_kategori ?? '');
    if (str_contains($kat, 'monitor') || str_contains($kat, 'monev') || str_contains($kat, 'evaluasi')) {
        $kolom2 = 'Hasil Monitoring & Evaluasi';
    } elseif (str_contains($kat, 'koordinasi')) {
        $kolom2 = 'Rangkaian Acara';
    } else {
        $kolom2 = 'Hasil Pembahasan';
    }

    $tanggalCetak = Carbon::parse($rapat->tanggal)->translatedFormat('d F Y');
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Notulensi - Pembahasan</title>
    <style>
        @page { size: A4 landscape; margin: 15mm 12mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; margin: 0; padding: 0; }
        table { width: 100%; border-collapse: collapse; border-spacing: 0; }

        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }

        tr { page-break-inside: avoid; }
        td, th {
            border: 1px solid #333;
            vertical-align: top;
            padding: 6px 8px;
            word-break: break-word;
            white-space: pre-wrap;
        }

        thead tr th { padding-top: 6px; padding-bottom: 6px; }
        tbody tr:first-child td { padding-top: 6px; }

        .title { font-weight: bold; font-size: 14px; margin: 0 0 6px 0; }
        .th-green { background: #28a745; color: #000; font-weight: bold; text-align: center; }

        .col-no   { width: 4%;  text-align: center; }
        .col-hasil{ width: 36%; }
        .col-rek  { width: 26%; }
        .col-pj   { width: 17%; }
        .col-tgl  { width: 17%; text-align: center; }

        /* ====== BLOK TTD ====== */
        .sign-stage{
            position: relative;   /* referensi absolute untuk kolom kanan */
            min-height: 60mm;     /* tinggi area tanda tangan */
            margin-top: 6mm;      /* LANGSUNG di bawah tabel; ubah kalau perlu */
        }

        /* Kiri (Notulis) — tetap seperti permintaan */
        .left-sign{
            width: 70mm;
            text-align: center;
        }
        .left-header{ margin-bottom: 4px; font-size: 11px; }
        .qr{
            width: 32mm;          /* ukuran QR konsisten kiri/kanan */
            height: auto;
            margin: 0 auto 6px auto;
            display: block;
        }
        .name{ font-weight: bold; text-decoration: underline; }
        .role{ font-weight: bold; margin-top: 2px; }
        .muted{ color:#666; font-size: 10px; }

        /* Kanan (Pimpinan) — diletakkan sejajar di sisi kanan */
        .right-sign{
            position: absolute;
            top: 0;               /* sejajar vertikal dengan blok kiri (tepat di bawah tabel) */
            right: 12mm;          /* jarak dari tepi kanan halaman (menyesuaikan @page margin right) */
            width: 70mm;
            text-align: center;
        }
        .right-header{ margin-bottom: 4px; font-size: 11px; }
    </style>
</head>
<body>

<div class="title">PEMBAHASAN :</div>

<table>
    <thead>
        <tr>
            <th class="th-green col-no">No.</th>
            <th class="th-green col-hasil">Hasil {{ $rapat->nama_kategori ?? 'Pembahasan' }}</th>
            <th class="th-green col-rek">Rekomendasi Tindak Lanjut</th>
            <th class="th-green col-pj">Penanggung Jawab</th>
            <th class="th-green col-tgl">Tgl. Penyelesaian</th>
        </tr>
    </thead>
    <tbody>
    @forelse($detail as $i => $row)
        <tr>
            <td class="col-no">{{ $i+1 }}</td>
            <td class="col-hasil">{!! $row->hasil_pembahasan !!}</td>
            <td class="col-rek">{!! $row->rekomendasi !!}</td>
            <td class="col-pj">{{ $row->penanggung_jawab ?? '-' }}</td>
            <td class="col-tgl">
                {{ $row->tgl_penyelesaian ? Carbon::parse($row->tgl_penyelesaian)->translatedFormat('d F Y') : '-' }}
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="5" style="text-align:center; padding:14px;">Belum ada data pembahasan.</td>
        </tr>
    @endforelse
    </tbody>
</table>

{{-- ====== AREA TANDA TANGAN ====== --}}
<div class="sign-stage">
    {{-- Kiri: Notulis (tidak diubah) --}}
    <div class="left-sign">
        <div class="left-header">Dibuat Oleh,</div>
        @if(!empty($qr_notulis_data))
            <img class="qr" src="{{ $qr_notulis_data }}">
        @else
            <div class="muted">(QR Notulis belum tersedia)</div>
        @endif
        <div class="name">{{ $notulis_nama ?? '-' }}</div>
        <div class="role">{{ $notulis_jabatan ?? 'Notulis' }}</div>
    </div>

    {{-- Kanan: Pimpinan (sejajar kanan) --}}
    <div class="right-sign">
        <div class="right-header">Manokwari, {{ $tanggalCetak }}</div>
        @if(!empty($qr_pimpinan_data))
            <img class="qr" src="{{ $qr_pimpinan_data }}">
        @else
            <div class="muted">(Menunggu approval pimpinan)</div>
        @endif
        <div class="name">{{ $pimpinan_nama ?? '-' }}</div>
        <div class="role">{{ $pimpinan_jabatan ?? 'Pimpinan Rapat' }}</div>
    </div>
</div>

</body>
</html>
