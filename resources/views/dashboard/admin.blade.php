@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-4">Dashboard Admin</h3>
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card shadow text-white bg-primary mb-3">
                <div class="card-body text-center">
                    <h4>{{ $jumlah_rapat }}</h4>
                    <p class="mb-0">Total Rapat</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow text-white bg-success mb-3">
                <div class="card-body text-center">
                    <h4>{{ $jumlah_undangan }}</h4>
                    <p class="mb-0">Total Undangan</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow text-white bg-info mb-3">
                <div class="card-body text-center">
                    <h4>{{ $jumlah_absensi }}</h4>
                    <p class="mb-0">Total Absensi</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow text-white bg-warning mb-3">
                <div class="card-body text-center">
                    <h4>{{ $jumlah_notulensi }}</h4>
                    <p class="mb-0">Total Notulensi</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow text-white bg-secondary mb-3">
                <div class="card-body text-center">
                    <h4>{{ $user_count }}</h4>
                    <p class="mb-0">Total Pengguna</p>
                </div>
            </div>
        </div>
    </div>
    <h5 class="mb-3">Rapat Terbaru</h5>
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover m-0">
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Tanggal</th>
                        <th>Tempat</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rapat_terbaru as $r)
                    <tr>
                        <td>{{ $r->judul }}</td>
                        <td>{{ \Carbon\Carbon::parse($r->tanggal)->format('d M Y') }}</td>
                        <td>{{ $r->tempat }}</td>
                    </tr>
                    @endforeach
                    @if($rapat_terbaru->count() == 0)
                    <tr>
                        <td colspan="3" class="text-center">Belum ada rapat.</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
