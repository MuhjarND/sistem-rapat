<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Laporan Rapat</title>
<style>
  @page { size: A4 portrait; margin: 12mm 10mm; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
  table { width:100%; border-collapse: collapse; }
  th, td { border:1px solid #222; padding:6px 8px; }
  thead th { background:#28a745; color:#000; font-weight:bold; text-align:center; }
  .right { text-align:right; }
  .center { text-align:center; }
  .title { text-align:center; font-size: 14px; font-weight:bold; margin-bottom:6px; }
  .subtitle { text-align:center; margin-bottom:12px; font-size: 11px; color:#555; }
</style>
</head>
<body>

<div class="title">LAPORAN REKAP RAPAT</div>
<div class="subtitle">
  Periode:
  {{ request('dari') ? \Carbon\Carbon::parse(request('dari'))->format('d/m/Y') : '-' }}
  s.d
  {{ request('sampai') ? \Carbon\Carbon::parse(request('sampai'))->format('d/m/Y') : '-' }}
</div>

<table>
  <thead>
    <tr>
      <th style="width:30px;">#</th>
      <th>Judul Rapat</th>
      <th style="width:120px;">Kategori</th>
      <th style="width:120px;">Tanggal</th>
      <th style="width:110px;">Tempat</th>
      <th style="width:75px;">Diundang</th>
      <th style="width:60px;">Hadir</th>
      <th style="width:90px;">Tidak Hadir</th>
      <th style="width:60px;">Izin</th>
      <th style="width:80px;">Notulensi</th>
    </tr>
  </thead>
  <tbody>
  @foreach($data as $i => $r)
    <tr>
      <td class="center">{{ $i+1 }}</td>
      <td>{{ $r->judul }}</td>
      <td>{{ $r->nama_kategori ?? '-' }}</td>
      <td>{{ \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') }} {{ $r->waktu_mulai }}</td>
      <td>{{ $r->tempat }}</td>
      <td class="center">{{ $r->jml_diundang }}</td>
      <td class="center">{{ $r->jml_hadir }}</td>
      <td class="center">{{ $r->jml_tidak_hadir }}</td>
      <td class="center">{{ $r->jml_izin }}</td>
      <td class="center">{{ $r->ada_notulensi ? 'Ada' : 'Belum' }}</td>
    </tr>
  @endforeach
  </tbody>
</table>

</body>
</html>
