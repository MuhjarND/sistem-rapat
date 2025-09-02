@php
    use Carbon\Carbon;

    // Tentukan judul kolom ke-2
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

        /* TTD */
        .ttd-table { width: 100%; margin-top: 30px; border: none; }
        .ttd-table td { border: none; text-align: center; vertical-align: bottom; }
        .ttd-name { text-decoration: underline; margin-top: 60px; }
    </style>
</head>
<body>

<div class="title">PEMBAHASAN :</div>

<table>
    <thead>
        <tr>
            <th class="th-green col-no">No.</th>
            <th class="th-green col-hasil">
                Hasil {{ $rapat->nama_kategori ?? 'Pembahasan' }}
            </th>
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

{{-- TANDA TANGAN --}}
<table class="ttd-table">
    <tr>
        <td>Dibuat Oleh,</td>
        <td>Manokwari, {{ $tanggalCetak }}</td>
    </tr>
    <tr>
        <td style="height:70px;"></td>
        <td></td>
    </tr>
    <tr>
        <td class="ttd-name">{{ $creator->name ?? '-' }}<br><span style="font-weight:bold;">Notulen</span></td>
        <td class="ttd-name">{{ $rapat->nama_pimpinan ?? '-' }}<br><span style="font-weight:bold;">{{ $rapat->jabatan_pimpinan ?? 'Pimpinan Rapat' }}</span></td>
    </tr>
</table>

</body>
</html>
