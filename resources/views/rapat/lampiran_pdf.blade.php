<div style="font-size: 13pt; font-weight:bold; margin-bottom: 10px;">LAMPIRAN</div>

<div style="margin-bottom:8px;">
    Surat Undangan {{ $rapat->judul }}<br>
    Nomor : {{ $rapat->nomor_undangan }}<br>
    Tanggal : {{ \Carbon\Carbon::parse($rapat->tanggal)->isoFormat('D MMMM Y') }}
</div>

<div style="margin-bottom: 5px;">DAFTAR PEJABAT/PEGAWAI YANG DIUNDANG</div>

@php
    // Ambil daftar jabatan unik (case-insensitive), hilangkan kosong/“-”
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

<br>

{{-- Tanda tangan approval --}}
<style>
    .qr { height: 90px; }
    .qr-caption { font-size: 10pt; color:#333; }
    .qr-placeholder { height: 90px; }
</style>

<div style="float:right; width:260px; text-align:left;">
    {{-- Approval 1 (wajib) --}}
    {{ $approval1->jabatan ?? 'Approval 1' }},<br>
    @if(!empty($qrA1) && file_exists(public_path($qrA1)))
        <img class="qr" src="{{ public_path($qrA1) }}"><br>
        <span class="qr-caption">Terverifikasi digital (Melalui Sistem Rapat)</span><br>
    @else
        <div class="qr-placeholder"></div><br>
    @endif
    <b>{{ $approval1->name ?? '-' }}</b>
</div>
