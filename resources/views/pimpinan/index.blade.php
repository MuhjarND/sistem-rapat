@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between mb-3">
        <h3>Daftar Pimpinan Rapat</h3>
        <a href="{{ route('pimpinan.create') }}" class="btn btn-primary">+ Tambah Pimpinan</a>
    </div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped m-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama</th>
                        <th>Jabatan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($daftar_pimpinan as $no => $pimpinan)
                    <tr>
                        <td>{{ $no+1 }}</td>
                        <td>{{ $pimpinan->nama }}</td>
                        <td>{{ $pimpinan->jabatan }}</td>
                        <td>
                            <a href="{{ route('pimpinan.edit', $pimpinan->id) }}" class="btn btn-warning btn-sm">Edit</a>
                            <form action="{{ route('pimpinan.destroy', $pimpinan->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus data ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                    @if($daftar_pimpinan->count() == 0)
                    <tr>
                        <td colspan="4" class="text-center">Belum ada data pimpinan.</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
