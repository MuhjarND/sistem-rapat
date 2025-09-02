@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-4">Dashboard Peserta</h3>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow text-white bg-primary mb-3">
                <div class="card-body text-center">
                    <h4>{{ $jumlah_undangan }}</h4>
                    <p class="mb-0">Total Undangan</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow text-white bg-success mb-3">
                <div class="card-body text-center">
                    <h4>{{ $jumlah_hadir }}</h4>
                    <p class="mb-0">Hadir</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow text-white bg-warning mb-3">
                <div class="card-body text-center">
                    <h4>{{ $jumlah_izin }}</h4>
                    <p class="mb-0">Izin</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow text-white bg-danger mb-3">
                <div class="card-body text-center">
                    <h4>{{ $jumlah_alfa }}</h4>
                    <p class="mb-0">Alfa</p>
                </div>
            </div>
        </div>
    </div>
    <h5 class="mb-3">Undangan Rapat Terbaru</h5>
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover m-0">
                <thead>
                    <tr>
                        <th>Judul Rapat</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($undangan_terbaru as $u)
                    <tr>
                        <td>{{ $u->judul }}</td>
                        <td>{{ \Carbon\Carbon::parse($u->tanggal)->format('d M Y') }}</td>
                        <td>
                            <span class="badge 
                                {{ $u->status == 'terkirim' ? 'badge-primary' : 
                                   ($u->status == 'diterima' ? 'badge-success' : 'badge-info') }}">
                                {{ ucfirst($u->status) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                    @if($undangan_terbaru->count() == 0)
                    <tr>
                        <td colspan="3" class="text-center">Tidak ada undangan baru.</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
