@php
    use Carbon\Carbon;

    $kop = public_path('kop_notulensi.jpg');
    $tgl = $rapat->tanggal ? Carbon::parse($rapat->tanggal)->translatedFormat('l, d F Y') : '-';
    $waktu = \App\Helpers\TimeHelper::short($rapat->waktu_mulai);

    $approval1 = \DB::table('users')
        ->where('id', $rapat->approval1_user_id ?? 0)
        ->select('name','jabatan','unit')
        ->first();
    $approval1Jabatan = $rapat->approval1_jabatan_manual ?: ($approval1->jabatan ?? '-');
    $approval1Text = $approval1
        ? trim(($approval1->name ?? '-') . ' - ' . $approval1Jabatan . (($approval1->unit ?? '') ? ' - '.$approval1->unit : ''))
        : ($rapat->nama_pimpinan ?? '-');

    $agendaText = $notulensi->agenda ?: ($rapat->deskripsi ?: $rapat->judul);
    $susunanText = $notulensi->susunan_agenda ?? '';
    $hasilRapat = $notulensi->hasil_rapat ?? '';
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Notulensi - Template B</title>
<style>
    @page { size: A4 portrait; margin: 12mm 10mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; margin:0; padding:0; }
    table { width:100%; border-collapse: collapse; }
    td, th { border:1px solid #000; padding:6px 8px; vertical-align: top; }

    .kop img { width:100%; height:auto; margin-bottom:6px; }
    .title-bar{
        text-align:center; font-size:13px; font-weight:bold; padding:6px;
        background:#dfead2; border:1px solid #000; margin-bottom:8px;
    }
    .section-title{
        font-weight:bold; background:#f4f4f4; padding:6px; border:1px solid #000;
    }
    .no-border td{ border:none; }
    .no-border .label{ width:28%; font-weight:bold; }

    .section-box{ border:1px solid #000; }
    .section-body{ padding:8px; line-height:1.5; }

    .list-line{ margin-left:12px; }

    .sig-table td{ border:none; padding:8px 6px; text-align:center; }
    .sig-box{
        border:1px solid #000; padding:8px; min-height:130px;
    }
    .sig-qr{ width:90px; height:90px; object-fit:contain; display:block; margin:0 auto 6px; }
    .sig-name{ font-weight:bold; margin-top:6px; }
    .sig-role{ font-size:10px; }
</style>
</head>
<body>

@if(file_exists($kop))
  <div class="kop"><img src="{{ $kop }}" alt="kop"></div>
@else
  <div style="text-align:center; margin-bottom:6px;">
    <strong>MAHKAMAH AGUNG REPUBLIK INDONESIA<br>PENGADILAN TINGGI AGAMA PAPUA BARAT</strong>
  </div>
@endif

<div class="title-bar">NOTULEN RAPAT</div>

<div class="section-box">
  <div class="section-title">A. URAIAN KEGIATAN RAPAT</div>
  <table class="no-border">
    <tr>
      <td class="label">Hari/Tanggal/Jam</td>
      <td>{{ ucfirst($tgl) }} / {{ $waktu }} WIT s/d Selesai</td>
    </tr>
    <tr>
      <td class="label">Tempat</td>
      <td>{{ $rapat->tempat ?? '-' }}</td>
    </tr>
    <tr>
      <td class="label">Pimpinan Rapat</td>
      <td>{{ $approval1Text }}</td>
    </tr>
    <tr>
      <td class="label">Peserta Rapat</td>
      <td>
        @if(!empty($daftar_peserta) && count($daftar_peserta))
          @foreach($daftar_peserta as $i => $p)
            <div class="list-line">{{ $i + 1 }}. {{ $p->name }}{{ !empty($p->jabatan) ? ' - '.$p->jabatan : '' }}</div>
          @endforeach
        @else
          -
        @endif
      </td>
    </tr>
  </table>
</div>

<div class="section-box" style="margin-top:8px;">
  <div class="section-title">B. AGENDA RAPAT</div>
  <div class="section-body">{!! nl2br(e($agendaText)) !!}</div>
</div>

<div class="section-box" style="margin-top:8px;">
  <div class="section-title">C. SUSUNAN AGENDA RAPAT</div>
  <div class="section-body">{!! nl2br(e($susunanText ?: '-')) !!}</div>
</div>

<div class="section-box" style="margin-top:8px;">
  <div class="section-title">D. HASIL RAPAT</div>
  <div class="section-body">
    @if(trim($hasilRapat) !== '')
      {!! $hasilRapat !!}
    @else
      -
    @endif
  </div>
</div>

<div class="section-box" style="margin-top:8px;">
  <div class="section-title">E. REKOMENDASI</div>
  <table>
    <thead>
      <tr>
        <th style="width:6%;">No</th>
        <th>Rekomendasi / Tindakan</th>
        <th style="width:26%;">Penanggung Jawab</th>
        <th style="width:16%;">Target</th>
      </tr>
    </thead>
    <tbody>
      @forelse($detail as $row)
        @php
          $assignees = \DB::table('notulensi_tugas')
              ->join('users','users.id','=','notulensi_tugas.user_id')
              ->where('id_notulensi_detail', $row->id)
              ->select('users.name','users.jabatan')
              ->get();
        @endphp
        <tr>
          <td style="text-align:center;">{{ $row->urut }}</td>
          <td>{!! $row->hasil_pembahasan !!}</td>
          <td>
            @if($assignees->count())
              @foreach($assignees as $a)
                <div>{{ $a->name }}{{ $a->jabatan ? ' - '.$a->jabatan : '' }}</div>
              @endforeach
            @else
              {{ $row->penanggung_jawab ?: '-' }}
            @endif
          </td>
          <td>{{ $row->tgl_penyelesaian ? \Carbon\Carbon::parse($row->tgl_penyelesaian)->format('d/m/Y') : '-' }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="4" style="text-align:center;">Belum ada rekomendasi.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

<table class="sig-table" style="margin-top:14px;">
  <tr>
    <td>
      <div class="sig-box">
        <div><b>Notulis</b></div>
        @if(!empty($qr_notulis_data))
          <img src="{{ $qr_notulis_data }}" class="sig-qr" alt="qr-notulis">
        @else
          <div style="height:90px;"></div>
        @endif
        <div class="sig-name">{{ $notulis_nama ?? '-' }}</div>
        <div class="sig-role">{{ $notulis_jabatan ?? 'Notulis' }}</div>
      </div>
    </td>
    <td>
      <div class="sig-box">
        <div><b>Pimpinan Rapat</b></div>
        @if(!empty($qr_pimpinan_data))
          <img src="{{ $qr_pimpinan_data }}" class="sig-qr" alt="qr-pimpinan">
        @else
          <div style="height:90px;"></div>
        @endif
        <div class="sig-name">{{ $pimpinan_nama ?? '-' }}</div>
        <div class="sig-role">{{ $pimpinan_jabatan ?? 'Pimpinan Rapat' }}</div>
      </div>
    </td>
  </tr>
</table>

</body>
</html>
