@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Daftar Absensi Rapat</h3>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <div class="card mt-3">
        <div class="card-body p-0">
            <table class="table table-bordered table-striped m-0">
                <thead>
                    <tr class="text-center">
                        <th width="5%">#</th>
                        <th>Judul Rapat</th>
                        <th>Kategori</th>
                        <th>Tanggal</th>
                        <th>Waktu</th>
                        <th>Tempat</th>
                        <th>Pimpinan</th>
                        <th>Peserta</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($daftar_rapat as $no => $rapat)
                    <tr>
                        <td class="text-center">{{ $no+1 }}</td>
                        <td>
                            <b>{{ $rapat->judul }}</b><br>
                            <small>{{ $rapat->nomor_undangan }}</small>
                        </td>
                        <td>{{ $rapat->nama_kategori ?? '-' }}</td>
                        <td>{{ \Carbon\Carbon::parse($rapat->tanggal)->format('d M Y') }}</td>
                        <td>{{ $rapat->waktu_mulai }}</td>
                        <td>{{ $rapat->tempat }}</td>
                        <td>
                            {{ $rapat->nama_pimpinan ?? '-' }}<br>
                            <small>{{ $rapat->jabatan_pimpinan ?? '-' }}</small>
                        </td>
                        <td class="text-center">
                            {{ $rapat->jumlah_peserta ?? 0 }}
                        </td>
                        <td class="text-center">
                            <a href="{{ route('absensi.export.pdf', $rapat->id) }}" class="btn btn-outline-primary btn-sm" target="_blank">
                                <i class="fa fa-download"></i> Download PDF
                            </a>
                            <a href="{{ route('rapat.show', $rapat->id) }}" class="btn btn-outline-info btn-sm" target="_blank">
                                <i class="fa fa-info-circle"></i> Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center">Belum ada rapat untuk absensi.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
