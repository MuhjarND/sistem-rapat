@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-4">Dashboard Notulis</h3>
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow text-white bg-info mb-3">
                <div class="card-body text-center">
                    <h4>{{ $jumlah_notulensi }}</h4>
                    <p class="mb-0">Notulensi Dibuat</p>
                </div>
            </div>
        </div>
    </div>
    <h5 class="mb-3">Notulensi Terbaru</h5>
    <div class="card mb-4">
        <div class="card-body p-0">
            <table class="table table-hover m-0">
                <thead>
                    <tr>
                        <th>Rapat</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($notulensi_terbaru as $n)
                    <tr>
                        <td>{{ $n->judul_rapat }}</td>
                        <td>{{ \Carbon\Carbon::parse($n->tanggal)->format('d M Y') }}</td>
                        <td>
                            <a href="{{ route('notulensi.show', $n->id) }}" class="btn btn-info btn-sm">Lihat</a>
                        </td>
                    </tr>
                    @endforeach
                    @if($notulensi_terbaru->count() == 0)
                    <tr>
                        <td colspan="3" class="text-center">Belum ada notulensi.</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
    <h5 class="mb-3">Rapat Belum Ada Notulensi</h5>
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover m-0">
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rapat_belum_dinotulen as $r)
                    <tr>
                        <td>{{ $r->judul }}</td>
                        <td>{{ \Carbon\Carbon::parse($r->tanggal)->format('d M Y') }}</td>
                    </tr>
                    @endforeach
                    @if($rapat_belum_dinotulen->count() == 0)
                    <tr>
                        <td colspan="2" class="text-center">Semua rapat sudah ada notulensi.</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
