@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Absensi Saya</h3>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @elseif(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="mb-4">
        <h5>Isi Absensi untuk Rapat yang Diundang</h5>
        @if($undangan->count())
        <form action="{{ route('absensi.isi') }}" method="POST" class="form-inline">
            @csrf
            <select name="id_rapat" class="form-control mr-2" required>
                <option value="">-- Pilih Rapat --</option>
                @foreach($undangan as $r)
                <option value="{{ $r->id_rapat }}">{{ $r->judul }} ({{ \Carbon\Carbon::parse($r->tanggal)->format('d M Y') }})</option>
                @endforeach
            </select>
            <select name="status" class="form-control mr-2" required>
                <option value="">-- Status --</option>
                <option value="hadir">Hadir</option>
                <option value="izin">Izin</option>
                <option value="alfa">Alfa</option>
            </select>
            <button class="btn btn-success">Absen</button>
        </form>
        @else
        <p class="text-muted">Tidak ada rapat yang perlu diabsen.</p>
        @endif
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped m-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Judul Rapat</th>
                        <th>Tanggal</th>
                        <th>Tempat</th>
                        <th>Status</th>
                        <th>Waktu Absen</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($absensi as $no => $a)
                    <tr>
                        <td>{{ $no+1 }}</td>
                        <td>{{ $a->judul }}</td>
                        <td>{{ \Carbon\Carbon::parse($a->tanggal)->format('d M Y') }}</td>
                        <td>{{ $a->tempat }}</td>
                        <td>
                            <span class="badge 
                                {{ $a->status == 'hadir' ? 'badge-success' : 
                                ($a->status == 'izin' ? 'badge-warning' : 'badge-danger') }}">
                                {{ ucfirst($a->status) }}
                            </span>
                        </td>
                        <td>{{ $a->waktu_absen ? \Carbon\Carbon::parse($a->waktu_absen)->format('d M Y H:i') : '-' }}</td>
                    </tr>
                    @endforeach
                    @if($absensi->count() == 0)
                    <tr>
                        <td colspan="6" class="text-center">Belum ada data absensi.</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
