@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Edit Kategori Rapat</h3>
    <div class="card">
        <div class="card-body">
            <form action="{{ route('kategori.update', $kategori->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label>Nama Kategori</label>
                    <input type="text" name="nama" class="form-control" required value="{{ old('nama', $kategori->nama) }}">
                </div>
                <div class="form-group form-check">
                    <input type="checkbox" class="form-check-input" id="butuh_pakaian" name="butuh_pakaian" value="1"
                           {{ old('butuh_pakaian', !empty($kategori->butuh_pakaian) ? 1 : 0) ? 'checked' : '' }}>
                    <label class="form-check-label" for="butuh_pakaian">Tampilkan field pakaian saat membuat rapat</label>
                </div>
                <button class="btn btn-primary">Update</button>
                <a href="{{ route('kategori.index') }}" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>
@endsection
