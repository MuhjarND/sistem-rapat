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
<div style="float:right; width:260px; text-align:left;">
    {{ $pimpinan->jabatan ?? '-' }},<br><br><br><br>
    <b>{{ $pimpinan->nama ?? '-' }}</b>
</div>
