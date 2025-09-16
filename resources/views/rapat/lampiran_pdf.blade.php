<div style="font-size: 13pt; font-weight:bold; margin-bottom: 10px;">LAMPIRAN</div>

<div style="margin-bottom:8px;">
    Surat Undangan {{ $rapat->judul }}<br>
    Nomor : {{ $rapat->nomor_undangan }}<br>
    Tanggal : {{ \Carbon\Carbon::parse($rapat->tanggal)->isoFormat('D MMMM Y') }}
</div>

<div style="margin-bottom: 5px;">DAFTAR PEJABAT/PEGAWAI YANG DIUNDANG</div>
<ol>
    @foreach($daftar_peserta as $peserta)
        <li>{{ $peserta->jabatan }}</li>
    @endforeach
</ol>

<br>

{{-- Tanda tangan approval --}}
<div style="float:right; width:260px; text-align:left;">
    {{-- Approval 1 (wajib) --}}
    {{ $approval1->jabatan ?? 'Approval 1' }},<br><br><br><br>
    <b>{{ $approval1->name ?? '-' }}</b>

    {{-- Approval 2 (opsional) --}}
    @if(!empty($approval2))
        <br><br>
        {{ $approval2->jabatan ?? 'Approval 2' }},<br><br><br><br>
        <b>{{ $approval2->name }}</b>
    @endif
</div>
