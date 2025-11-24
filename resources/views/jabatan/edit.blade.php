@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Edit Jabatan</h3>
        <a href="{{ route('jabatan.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('jabatan.update', $jab->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label>Nama Jabatan</label>
                    <input type="text" name="nama" class="form-control" required maxlength="150" value="{{ old('nama', $jab->nama) }}">
                </div>
                <div class="form-group">
                    <label>Kategori</label>
                    <input type="text" name="kategori" class="form-control" maxlength="100" value="{{ old('kategori', $jab->kategori) }}">
                </div>
                <div class="form-group">
                    <label>Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="3">{{ old('keterangan', $jab->keterangan) }}</textarea>
                </div>
                <button class="btn btn-primary">Simpan</button>
                <a href="{{ route('jabatan.index') }}" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>
@endsection
