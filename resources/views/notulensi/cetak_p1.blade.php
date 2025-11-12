@php
    use Carbon\Carbon;

    // Tanggal
    $tgl = Carbon::parse($rapat->tanggal)->translatedFormat('l, d F Y');

    // KOP
    $kop = public_path('kop_notulensi.jpg');

    // === Ambil Approval 1 dari users ===
    $approval1 = \DB::table('users')
        ->where('id', $rapat->approval1_user_id ?? 0)
        ->select('name','jabatan','unit')
        ->first();

    $approval1Text = $approval1
        ? trim(
            ($approval1->name ?? '-') .
            ' — ' . ($approval1->jabatan ?? '-') .
            (($approval1->unit ?? '') ? ' · '.$approval1->unit : '')
          )
        : '-';
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Notulensi - Informasi</title>
<style>
    @page { size: A4 portrait; margin: 15mm 12mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; margin:0; padding:0; }
    table { width:100%; border-collapse: collapse; }
    td, th { border:1px solid #000; padding:6px 8px; vertical-align: top; }

    .kop img { width:100%; height:auto; margin-bottom:8px; }
    .judul { text-align:center; font-size:13px; font-weight:bold; margin: 6px 0; }

    .tbl-header th, .tbl-header td { text-align:center; font-weight:bold; }
    .tbl-header td { font-weight:normal; }

    .tbl-info td { border:1px solid #000; }
    .hd { background:#28a745; color:#fff; font-weight:bold; width:28%; }
    .agenda { background:#28a745; color:#000; font-weight:bold; text-align:center; }
    .agenda-content { padding-left:15px; }
</style>
</head>
<body>

{{-- KOP --}}
@if(file_exists($kop))
  <div class="kop"><img src="{{ $kop }}" alt="kop"></div>
@else
  <div style="text-align:center; margin-bottom:6px;">
    <strong>MAHKAMAH AGUNG REPUBLIK INDONESIA<br>PENGADILAN TINGGI AGAMA PAPUA BARAT</strong>
  </div>
@endif

{{-- INFORMASI RAPAT --}}
<table class="tbl-info">
    <tr>
        <td class="hd">Jenis Kegiatan</td>
        <td>{{ $rapat->nama_kategori ?? '-' }}</td>
    </tr>
    <tr>
        <td class="hd">Hari/Tanggal/Jam</td>
        <td>{{ ucfirst($tgl) }}, {{ $rapat->waktu_mulai }}</td>
    </tr>
    <tr>
        <td class="hd">Tempat</td>
        <td>{{ $rapat->tempat }}</td>
    </tr>
    {{-- Ganti Pimpinan Rapat -> Approval 1 --}}
    <tr>
        <td class="hd">Pemimpin Rapat</td>
        <td>{{ $approval1Text }}</td>
    </tr>
    <tr>
        <td class="hd">Peserta</td>
        <td>{{ $jumlah_peserta }} Orang</td>
    </tr>
    <tr>
        <td class="agenda" colspan="2">Agenda Rapat</td>
    </tr>
    <tr>
        <td colspan="2" class="agenda-content">- {{ $rapat->deskripsi ?: $rapat->judul }}</td>
    </tr>
</table>

</body>
</html>
