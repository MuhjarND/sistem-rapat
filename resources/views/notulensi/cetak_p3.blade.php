@php
    $kop = public_path('Screenshot 2025-08-23 121254.jpeg'); // kop untuk dokumentasi
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Notulensi - Dokumentasi</title>
<style>
    @page { size: A4 portrait; margin: 10mm 10mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; margin:0; padding:0; }

    /* Bingkai keseluruhan */
    .frame {
        border: 1px solid #000;
        padding: 10px;
    }

    .kop img { width:100%; height:auto; margin-bottom:4px; }

    /* garis pemisah antara kop dan judul */
    .separator {
        border-top: 1px solid #000;
        margin: 6px 0 10px;
    }

    .judul-blok {
        background:#28a745;
        color:#000;
        font-weight:bold;
        text-align:center;
        padding:6px;
        margin-bottom:12px;
    }

    .foto { text-align:center; margin-bottom:12px; }
    .foto img {
        max-width:60%;
        height:auto;
        padding:2px;
        background:#fff;
    }
    .caption { font-size:9pt; margin-top:4px; color:#555; }
</style>
</head>
<body>

<div class="frame">

  {{-- KOP --}}
  @if(file_exists($kop))
    <div class="kop"><img src="{{ $kop }}" alt="kop"></div>
  @else
    <div style="text-align:center; margin-bottom:4px;">
      <strong>PENGADILAN TINGGI AGAMA PAPUA BARAT</strong><br>
      <small>(kop tidak ditemukan)</small>
    </div>
  @endif

  {{-- GARIS PEMISAH --}}
  <div class="separator"></div>

  {{-- JUDUL DOKUMENTASI --}}
  <div class="judul-blok">
    DOKUMENTASI KEGIATAN RAPAT 
    {{ strtoupper($rapat->nama_kategori ?? '') }}<br>
    PENGADILAN TINGGI AGAMA PAPUA BARAT
  </div>

  {{-- FOTO-FOTO --}}
  @if(count($dokumentasi))
    @foreach($dokumentasi as $dok)
      <div class="foto">
        @php $abs = public_path($dok->file_path); @endphp
        @if(file_exists($abs))
          <img src="{{ $abs }}" alt="dokumentasi">
        @else
          <div style="color:#888;">(gambar tidak ditemukan)</div>
        @endif
        @if($dok->caption)<div class="caption">{{ $dok->caption }}</div>@endif
      </div>
    @endforeach
  @else
    <p style="text-align:center; color:#888;">(Belum ada dokumentasi)</p>
  @endif

</div>

</body>
</html>
