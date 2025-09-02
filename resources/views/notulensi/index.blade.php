@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Notulen Rapat</h3>

    @if(session('success'))
        <div class="alert alert-success mt-2">{{ session('success') }}</div>
    @endif

    <div class="row mt-3">
        {{-- Belum dibuat --}}
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <b>Rapat Belum Memiliki Notulen</b>
                    <span class="badge badge-secondary">{{ $rapat_belum_notulen->count() }}</span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped m-0">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th>Judul</th>
                                <th>Kategori</th>
                                <th>Tanggal</th>
                                <th>Waktu</th>
                                <th>Pimpinan</th>
                                <th>Tempat</th>
                                <th width="12%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rapat_belum_notulen as $i => $r)
                            <tr>
                                <td>{{ $i+1 }}</td>
                                <td>
                                    <b>{{ $r->judul }}</b><br>
                                    <small>{{ $r->nomor_undangan }}</small>
                                </td>
                                <td>{{ $r->nama_kategori ?? '-' }}</td>
                                <td>{{ \Carbon\Carbon::parse($r->tanggal)->format('d M Y') }}</td>
                                <td>{{ $r->waktu_mulai }}</td>
                                <td>
                                    {{ $r->nama_pimpinan ?? '-' }}<br>
                                    <small>{{ $r->jabatan_pimpinan ?? '-' }}</small>
                                </td>
                                <td>{{ $r->tempat }}</td>
                                <td>
                                    <a href="{{ route('notulensi.create', $r->id) }}" class="btn btn-primary btn-sm">Buat Notulen</a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="text-center p-3">Semua rapat sudah memiliki notulensi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Sudah dibuat --}}
        <div class="col-md-12">
            <div class="card mb-2">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <b>Rapat Sudah Memiliki Notulen</b>
                    <span class="badge badge-success">{{ $rapat_sudah_notulen->count() }}</span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped m-0">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th>Judul</th>
                                <th>Kategori</th>
                                <th>Tanggal</th>
                                <th>Pimpinan</th>
                                <th>Tempat</th>
                                <th width="18%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rapat_sudah_notulen as $i => $r)
                            <tr>
                                <td>{{ $i+1 }}</td>
                                <td>
                                    <b>{{ $r->judul }}</b><br>
                                    <small>{{ $r->nomor_undangan }}</small>
                                </td>
                                <td>{{ $r->nama_kategori ?? '-' }}</td>
                                <td>{{ \Carbon\Carbon::parse($r->tanggal)->format('d M Y') }}</td>
                                <td>
                                    {{ $r->nama_pimpinan ?? '-' }}<br>
                                    <small>{{ $r->jabatan_pimpinan ?? '-' }}</small>
                                </td>
                                <td>{{ $r->tempat }}</td>
                                <td>
                                    <a href="{{ route('notulensi.show', $r->id_notulensi) }}" class="btn btn-info btn-sm">Lihat</a>
                                    <a href="{{ route('notulensi.edit', $r->id_notulensi) }}" class="btn btn-warning btn-sm">Edit</a>
                                    {{-- (nanti) cetak PDF notulen --}}
                                    {{-- <a href="{{ route('notulensi.cetak.pdf', $r->id_notulensi) }}" class="btn btn-outline-primary btn-sm" target="_blank">Cetak</a> --}}
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center p-3">Belum ada notulen yang dibuat.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
