@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Absensi Rapat: {{ $rapat->judul }}</h3>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <ul class="list-group mb-3">
        <li class="list-group-item"><b>Tanggal:</b> {{ \Carbon\Carbon::parse($rapat->tanggal)->format('d M Y') }}</li>
        <li class="list-group-item"><b>Waktu:</b> {{ $rapat->waktu_mulai }}</li>
        <li class="list-group-item"><b>Tempat:</b> {{ $rapat->tempat }}</li>
    </ul>

    @if($sudah_absen)
        <div class="alert alert-info">Anda sudah tercatat hadir pada rapat ini.</div>
        <a href="{{ url()->previous() }}" class="btn btn-secondary">Kembali</a>
    @else
        <form action="{{ route('absensi.scan.save', $rapat->token_qr) }}" method="POST">
            @csrf
            <button class="btn btn-success btn-lg">Konfirmasi Hadir</button>
            <a href="{{ url()->previous() }}" class="btn btn-secondary">Batal</a>
        </form>
    @endif
</div>
@endsection
