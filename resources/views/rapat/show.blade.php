@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Detail Rapat</h3>
    <div class="card mb-3">
        <div class="card-body">
            <ul class="list-group list-group-flush mb-2">
                <li class="list-group-item"><b>Nomor Undangan:</b> {{ $rapat->nomor_undangan }}</li>
                <li class="list-group-item"><b>Judul:</b> {{ $rapat->judul }}</li>
                <li class="list-group-item"><b>Deskripsi:</b> {{ $rapat->deskripsi }}</li>
                <li class="list-group-item"><b>Tanggal:</b> {{ \Carbon\Carbon::parse($rapat->tanggal)->format('d M Y') }}</li>
                <li class="list-group-item"><b>Waktu Mulai:</b> {{ $rapat->waktu_mulai }}</li>
                <li class="list-group-item"><b>Tempat:</b> {{ $rapat->tempat }}</li>
            </ul>
            <hr>
            <h5>Peserta Undangan</h5>
            <ul>
                @foreach($daftar_peserta as $peserta)
                <li>{{ $peserta->name }} ({{ $peserta->email }})</li>
                @endforeach
            </ul>
            {{-- QR Absensi (hanya admin/notulis, atur sesuai kebijakan) --}}
            @auth
            @if(in_array(Auth::user()->role, ['admin','notulis']))
                <hr>
                <h5>QR Code Absensi</h5>
                <p>Peserta dapat melakukan scan QR ini untuk absen hadir.</p>
                <div class="border p-3 d-inline-block">
                    {!! QrCode::size(220)->generate(route('absensi.scan', $rapat->token_qr)) !!}
                </div>
                <div class="mt-2">
                    <small>Link langsung: <a href="{{ route('absensi.scan', $rapat->token_qr) }}" target="_blank">{{ route('absensi.scan', $rapat->token_qr) }}</a></small>
                </div>
                <a href="{{ route('absensi.export.pdf', $rapat->id) }}" class="btn btn-outline-primary mt-3">Export Laporan Absensi (PDF)</a>
            @endif
            @endauth

            @if(Auth::user()->role == 'admin')
                <a href="{{ route('rapat.undangan.pdf', $rapat->id) }}" class="btn btn-outline-primary mb-3" target="_blank">
                    <i class="fa fa-file-pdf-o"></i> Export Undangan PDF
                </a>
            @endif
            <a href="{{ route('rapat.index') }}" class="btn btn-secondary mt-3">Kembali</a>
        </div>
    </div>
</div>
@endsection
