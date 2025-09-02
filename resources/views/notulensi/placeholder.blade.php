<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  @page { size: A4 portrait; margin: 15mm 12mm; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
  .box { border:1px solid #000; padding:12px; }
  .title { text-align:center; font-weight:bold; font-size:14px; margin-bottom:10px; }
  .muted { color:#666; text-align:center; }
</style>
</head>
<body>
  <div class="box">
    <div class="title">NOTULENSI RAPAT</div>
    <p class="muted">Belum ada notulensi untuk rapat: <strong>{{ $rapat->judul }}</strong></p>
    <p class="muted">Tanggal: {{ \Carbon\Carbon::parse($rapat->tanggal)->translatedFormat('d F Y') }}</p>
  </div>
</body>
</html>
