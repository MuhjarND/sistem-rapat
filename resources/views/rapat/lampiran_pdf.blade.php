<div style="font-size: 13pt; font-weight:bold; margin-bottom: 10px;">LAMPIRAN</div>

@php
    $approval1Jabatan = $rapat->approval1_jabatan_manual ?: ($approval1->jabatan ?? 'Approval 1');
    $approval1Nama = \App\Helpers\NameHelper::withoutTitles($approval1->name ?? '-');
@endphp

<div style="margin-bottom:8px;">
    Surat Undangan {{ $approval1Jabatan }}
    @if(!empty($approval1->unit))
        {{ $approval1->unit }}
    @endif
    <br>
    Nomor : {{ $rapat->nomor_undangan }}<br>
    Tanggal : {{ \Carbon\Carbon::parse($rapat->tanggal)->isoFormat('D MMMM Y') }}
</div><br>

<div style="margin-bottom: 5px; text-align:center; font-weight:bold;">DAFTAR PEJABAT/PEGAWAI YANG DIUNDANG</div><br>

@php
    $jabatanUnik = collect($daftar_peserta ?? [])
        ->map(function($p){ return trim((string)($p->jabatan ?? '')); })
        ->filter(function($v){ return $v !== '' && $v !== '-'; })
        ->unique(function($v){ return mb_strtolower($v); })
        ->values();
@endphp

@if($jabatanUnik->isNotEmpty())
    <ol>
        @foreach($jabatanUnik as $jab)
            <li>{{ $jab }}</li>
        @endforeach
    </ol>
@else
    <div style="color:#666;">(Tidak ada jabatan yang dapat ditampilkan)</div>
@endif

<br><br>

{{-- Tanda tangan approval --}}
<style>
    .qr { height: 90px; }
    .qr-caption { font-size: 10pt; color:#333; }
    .qr-placeholder { height: 90px; }
</style>

<div style="float:right; width:300px; text-align:left;">
    {{-- Approval 1 (wajib) --}}
    <b>{{ $approval1Jabatan }},</b><br>
    @if(!empty($approval1->unit))
        <b>{{ $approval1->unit }}</b><br>
    @endif
    @if(!empty($qrA1) && file_exists(public_path($qrA1)))
        <img class="qr" src="{{ public_path($qrA1) }}"><br>
        <span class="qr-caption">Terverifikasi digital (Melalui SMART)</span><br>
    @else
        <div class="qr-placeholder"></div><br>
    @endif
    <b>{{ $approval1Nama }}</b>
</div>
