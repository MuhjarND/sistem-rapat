<!doctype html>
<html lang="id">
  <head>
    <meta charset="utf-8">
    <title>Lembar Tanda Tangan Notulensi</title>
    <style>
      @page { margin: 40px 50px 80px; }
      body {
        font-family: "DejaVu Sans", sans-serif;
        font-size: 12px;
        color: #000;
        position: relative;
      }
      .title {
        text-align: center;
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 8px;
      }
      .meta {
        text-align: center;
        font-size: 11px;
        color: #444;
        margin-bottom: 24px;
      }
      .sig-row {
        position: absolute;
        bottom: 40px;
        left: 0;
        right: 0;
        width: 100%;
      }
      .sig {
        width: 50%;
        float: left;
        text-align: center;
      }
      .sig img {
        width: 120px;
        height: 120px;
        margin: 6px 0 4px;
      }
      .sig .name {
        font-weight: 700;
        margin-top: 4px;
      }
      .sig .role {
        font-size: 11px;
        color: #333;
      }
      .clear { clear: both; }
      .note {
        position: absolute;
        bottom: 10px;
        left: 0;
        right: 0;
        text-align: center;
        font-size: 10px;
        color: #444;
      }
    </style>
  </head>
  <body>
    <div class="title">Lembar Tanda Tangan</div>
    <div class="meta">
      {{ $rapat->judul ?? '-' }}<br>
      {{ isset($rapat->tanggal) ? \Carbon\Carbon::parse($rapat->tanggal)->translatedFormat('d F Y') : '-' }}
    </div>

    <div class="sig-row">
      <div class="sig">
        <div>Notulis</div>
        @if(!empty($qr_notulis_data))
          <img src="{{ $qr_notulis_data }}" alt="QR Notulis">
        @endif
        <div class="name">{{ $notulis_nama ?? '-' }}</div>
        <div class="role">{{ $notulis_jabatan ?? 'Notulis' }}</div>
      </div>
      <div class="sig">
        <div>Approval</div>
        @if(!empty($qr_approver_data))
          <img src="{{ $qr_approver_data }}" alt="QR Approval">
        @endif
        <div class="name">{{ $approver_nama ?? '-' }}</div>
        <div class="role">{{ $approver_jabatan ?? 'Pimpinan Rapat' }}</div>
      </div>
      <div class="clear"></div>
    </div>

    <div class="note">Terverifikasi secara digital melalui QR.</div>
  </body>
</html>
