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

        /* ===== Tabel utama ===== */
        .tb { border:1px solid #000; }
        .tb th, .tb td {
            border:1px solid #000;
            padding:4px 5px;
            line-height:1.15;
        }
        .center { text-align: center; vertical-align: middle; }

        /* Rata tengah untuk kolom jabatan dan instansi */
        .jabatan-col, .instansi-col {
            text-align:center;
            vertical-align:middle;
        }

        /* ===== Kolom tanda tangan ===== */
        .ttd-col { text-align:center; vertical-align:middle; }
        .ttd-box {
            height: 70px;
            display:flex; align-items:center; justify-content:center;
        }
        .ttd-img {
            display:block; margin:0 auto;
            max-height:65px;
            max-width:160px;
            height:auto; width:auto;
        }
        .ttd-empty {
            width:120px; height:60px;
            border:1px dashed #777;
            margin:0 auto;
        }
        .muted { color:#666; font-size: 9pt; margin-top:1px; text-align:center; }

        .ttd { float:right; width:260px; text-align:left; }
        .qr-note { font-size: 10pt; color:#444; line-height:1.35; }
        .clearfix::after { content:""; display:table; clear:both; }
    </style>
</head>
<body>
@php
  $get = function ($row, $key, $default = null) {
      if (is_array($row))  return array_key_exists($key, $row) ? $row[$key] : $default;
      if (is_object($row)) return isset($row->{$key}) ? $row->{$key} : $default;
      return $default;
  };

  $R = isset($rap) ? $rap : (isset($rapat) ? $rapat : []);
  $nama_kategori = $get($R, 'nama_kategori', '-');
  $judul         = $get($R, 'judul', '-');
  $tanggalHuman  = $get($R, 'tanggal_human');
  $tanggal       = $get($R, 'tanggal');
  $waktu_mulai   = $get($R, 'waktu_mulai');
  $tempat        = $get($R, 'tempat', '-');

  if (!$tanggalHuman && $tanggal) {
      try {
          \Carbon\Carbon::setLocale('id');
          $tanggalHuman = \Carbon\Carbon::parse($tanggal)->isoFormat('dddd, D MMMM Y');
      } catch (\Throwable $e) {
          $tanggalHuman = $tanggal;
      }
  }
  if (!$tanggalHuman) $tanggalHuman = '-';

  $approver_nama    = isset($approver) ? $get($approver,'nama','-') : '-';
  $approver_jabatan = isset($approver) ? $get($approver,'jabatan','Penanggung Jawab') : 'Penanggung Jawab';
  $peserta = is_iterable($peserta ?? null) ? $peserta : [];
@endphp

    {{-- KOP --}}
    @if(!empty($kop) && @is_file($kop))
        <div><img src="{{ $kop }}" style="width:100%; height:auto;"></div>
        <br>
    @endif

    {{-- Meta rapat --}}
    <table class="meta" style="margin-bottom: 10px;">
        <tr><td width="22%">Jenis Kegiatan</td><td width="2%">:</td><td>{{ $nama_kategori }}</td></tr>
        <tr><td>Nama Kegiatan</td><td>:</td><td>{{ $judul }}</td></tr>
        <tr><td>Hari/Tanggal</td><td>:</td><td>{{ $tanggalHuman }}</td></tr>
        <tr><td>Waktu</td><td>:</td><td>{{ $waktu_mulai ? ($waktu_mulai.' WIT s/d selesai') : '-' }}</td></tr>
        <tr><td>Tempat</td><td>:</td><td>{{ $tempat }}</td></tr>
    </table>
    <br>

    {{-- ===== Tabel peserta ===== --}}
    <table class="tb">
        <thead>
            <tr class="center">
                <th width="5%">No</th>
                <th width="27%">Nama</th>
                <th width="21%">Jabatan</th>
                <th width="18%">Instansi</th>
                <th width="21%">Tanda Tangan</th>
                <th width="8%">Ket</th>
            </tr>
        </thead>
        <tbody>
            @php $no=1; @endphp
            @forelse($peserta as $p)
                @php
                  $nm    = $get($p,'name', $get($p,'nama','-'));
                  $jab   = $get($p,'jabatan','-');
                  $unit  = $get($p,'unit','-');
                  $stat  = strtoupper($get($p,'status','-'));
                  $ttd   = $get($p,'ttd_data');
                //   $wabs  = $get($p,'waktu_absen');
                @endphp
                <tr>
                    <td class="center">{{ $no++ }}</td>
                    <td><b>{{ $nm }}</b></td>
                    <td class="jabatan-col">{{ $jab ?: '-' }}</td>
                    <td class="instansi-col">{{ $unit ?: '-' }}</td>
                    <td class="ttd-col">
                        <div class="ttd-box">
                            @if(!empty($ttd))
                                <img class="ttd-img" src="{{ $ttd }}" alt="TTD {{ $nm }}">
                            @else
                                <div class="ttd-empty"></div>
                            @endif
                        </div>
                        {{-- @if(!empty($wabs))
                            <div class="muted">{{ $wabs }} WIT</div>
                        @endif --}}
                    </td>
                    <td class="center">{{ $stat ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="center">Tidak ada data peserta.</td></tr>
            @endforelse
        </tbody>
    </table>
    <br><br>

    {{-- ===== Blok TTD / QR ===== --}}
    <div class="clearfix">
        <table class="ttd">
            <tr>
                <td>
                    {{-- <div>{{ $approver_jabatan ?: 'Penanggung Jawab' }},</div> <br><br> --}}
                    <div>Ketua Panitia,</div> <br><br><br><br><br>

                    {{-- @if(!empty($qrSrc))
                        <img src="{{ $qrSrc }}" style="width:130px; height:auto; margin:8px 0;">
                        <div class="qr-note">Terverifikasi digital (Melalui Aplikasi Sistem Rapat)</div>
                    @else
                        <div class="qr-note" style="margin:8px 0;">
                            <i>MENUNGGU APPROVAL ABSENSI</i><br>
                            QR absensi akan muncul otomatis setelah seluruh approval ABSENSI selesai.
                        </div>
                    @endif --}}

                    <b>I M R A N</b>
                    {{-- <b>{{ $approver_nama ?: '-' }}</b> --}}
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
