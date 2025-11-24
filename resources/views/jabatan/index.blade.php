@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Kelola Jabatan</h3>
        <a href="{{ route('jabatan.index') }}" class="btn btn-outline-secondary btn-sm">Refresh</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row">
        <div class="col-md-5">
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Tambah Jabatan</h5>
                    <form action="{{ route('jabatan.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label>Nama Jabatan</label>
                            <input type="text" name="nama" class="form-control" required maxlength="150" value="{{ old('nama') }}">
                        </div>
                        <div class="form-group">
                            <label>Kategori</label>
                            <input type="text" name="kategori" class="form-control" maxlength="100" placeholder="contoh: Struktural / Fungsional" value="{{ old('kategori') }}">
                        </div>
                        <div class="form-group">
                            <label>Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="3">{{ old('keterangan') }}</textarea>
                        </div>
                        <button class="btn btn-primary">Simpan</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Daftar Jabatan</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Kategori</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($jabatan as $j)
                                    <tr>
                                        <td>{{ $j->nama }}</td>
                                        <td>{{ $j->kategori ?? '—' }}</td>
                                        <td class="text-nowrap">
                                            <a href="{{ route('jabatan.edit', $j->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                            <form action="{{ route('jabatan.destroy', $j->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus jabatan ini?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-danger">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted">Belum ada data jabatan.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Daftar Pengguna & Jabatannya</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Jabatan</th>
                                    <th>Kategori</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $u)
                                    @php
                                        $jabNama = $u->jabatan_ref ?? $u->jabatan ?? '—';
                                    @endphp
                                    <tr>
                                        <td>{{ $u->name }}</td>
                                        <td>{{ $u->email }}</td>
                                        <td>{{ $u->role }}</td>
                                        <td>{{ $jabNama }}</td>
                                        <td>{{ $u->kategori ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted">Tidak ada pengguna.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <small class="text-muted d-block mt-2">Catatan: untuk mengikat jabatan ke pengguna, set kolom jabatan_id di manajemen pengguna atau update langsung pada data pengguna.</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
