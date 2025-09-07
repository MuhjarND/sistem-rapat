@extends('layouts.app')

@section('title','Detail Rapat')

@section('content')
<div class="container">
    <h3 class="mb-4">Detail Rapat</h3>
    <div class="card p-4">

        <div class="row">
            {{-- Kolom Kiri: Info Rapat + Peserta --}}
            <div class="col-lg-7">
                <ul class="list-group list-group-flush mb-3">
                    <li class="list-group-item bg-transparent text-light">
                        <strong>Nomor Undangan:</strong> {{ $rapat->nomor_undangan }}
                    </li>
                    <li class="list-group-item bg-transparent text-light">
                        <strong>Judul:</strong> {{ $rapat->judul }}
                    </li>
                    <li class="list-group-item bg-transparent text-light">
                        <strong>Deskripsi:</strong> {{ $rapat->deskripsi }}
                    </li>
                    <li class="list-group-item bg-transparent text-light">
                        <strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($rapat->tanggal)->format('d M Y') }}
                    </li>
                    <li class="list-group-item bg-transparent text-light">
                        <strong>Waktu Mulai:</strong> {{ $rapat->waktu_mulai }}
                    </li>
                    <li class="list-group-item bg-transparent text-light">
                        <strong>Tempat:</strong> {{ $rapat->tempat }}
                    </li>
                </ul>

                <h5 class="mt-4">Peserta Undangan</h5>
                <ul>
                    @foreach($daftar_peserta as $peserta)
                        <li>{{ $peserta->name }} ({{ $peserta->email }})</li>
                    @endforeach
                </ul>
            </div>

            {{-- Kolom Kanan: QR + Tombol PDF --}}
            <div class="col-lg-5 border-left">
                @auth
                @if(in_array(Auth::user()->role, ['admin','notulis']))
                    <h5>QR Code Absensi</h5>
                    <p class="text-muted">Peserta dapat scan QR ini untuk absen hadir.</p>
                    <div class="p-3 mb-2 rounded" style="background:rgba(255,255,255,.05);display:inline-block;">
                        {!! QrCode::size(180)->generate(route('absensi.scan', $rapat->token_qr)) !!}
                    </div>
                    <div class="mt-2 mb-3">
                        <small class="text-muted">Link langsung: 
                            <a href="{{ route('absensi.scan', $rapat->token_qr) }}" target="_blank">
                                {{ route('absensi.scan', $rapat->token_qr) }}
                            </a>
                        </small>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('absensi.export.pdf', $rapat->id) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-file-pdf"></i> Laporan Absensi
                        </a>
                        <a href="{{ route('rapat.undangan.pdf', $rapat->id) }}" class="btn btn-primary btn-sm ml-2">
                            <i class="fas fa-envelope-open-text"></i> Undangan
                        </a>
                    </div>
                @endif
                @endauth
            </div>
        </div>

        {{-- Tombol Kembali --}}
        <div class="mt-4">
            <a href="{{ route('rapat.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>
</div>
@endsection
