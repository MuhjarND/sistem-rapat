@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Edit Pimpinan Rapat</h3>
    <div class="card">
        <div class="card-body">
            <form action="{{ route('pimpinan.update', $pimpinan->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label>Nama</label>
                    <input type="text" name="nama" class="form-control" required value="{{ old('nama', $pimpinan->nama) }}">
                </div>
                <div class="form-group">
                    <label>Jabatan</label>
                    <input type="text" name="jabatan" class="form-control" required value="{{ old('jabatan', $pimpinan->jabatan) }}">
                </div>
                <button class="btn btn-primary">Update</button>
                <a href="{{ route('pimpinan.index') }}" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>
@endsection
