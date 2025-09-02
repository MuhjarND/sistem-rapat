@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Edit Rapat</h3>
    <div class="card">
        <div class="card-body">
            <form action="{{ route('rapat.update', $rapat->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label>Nomor Undangan</label>
                    <input type="text" name="nomor_undangan" class="form-control" required value="{{ old('nomor_undangan', $rapat->nomor_undangan) }}">
                </div>
                <div class="form-group">
                    <label>Judul</label>
                    <input type="text" name="judul" class="form-control" required value="{{ old('judul', $rapat->judul) }}">
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" class="form-control">{{ old('deskripsi', $rapat->deskripsi) }}</textarea>
                </div>
                <div class="form-group">
                    <label>Kategori Rapat</label>
                    <select name="id_kategori" class="form-control" required>
                        <option value="">-- Pilih Kategori --</option>
                        @foreach($daftar_kategori as $kategori)
                            <option value="{{ $kategori->id }}"
                                {{ old('id_kategori', $rapat->id_kategori ?? '') == $kategori->id ? 'selected' : '' }}>
                                {{ $kategori->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" required value="{{ old('tanggal', $rapat->tanggal) }}">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Waktu Mulai</label>
                        <input type="time" name="waktu_mulai" class="form-control" required value="{{ old('waktu_mulai', $rapat->waktu_mulai) }}">
                    </div>
                </div>
                <div class="form-group">
                    <label>Tempat</label>
                    <input type="text" name="tempat" class="form-control" required value="{{ old('tempat', $rapat->tempat) }}">
                </div>
                <div class="form-group">
                    <label>Pimpinan Rapat</label>
                    <select name="id_pimpinan" class="form-control" required>
                        <option value="">-- Pilih Pimpinan --</option>
                        @foreach($daftar_pimpinan as $pimpinan)
                            <option value="{{ $pimpinan->id }}"
                            @if(old('id_pimpinan', $rapat->id_pimpinan ?? '') == $pimpinan->id) selected @endif>
                            {{ $pimpinan->nama }} - {{ $pimpinan->jabatan }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Pilih Peserta Undangan</label>
                    <div class="card p-2" style="max-height:220px;overflow:auto;">
                        @foreach($daftar_peserta as $peserta)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="peserta[]" value="{{ $peserta->id }}"
                                id="peserta{{ $peserta->id }}"
                                {{ in_array($peserta->id, $peserta_terpilih) ? 'checked' : '' }}>
                            <label class="form-check-label" for="peserta{{ $peserta->id }}">{{ $peserta->name }} ({{ $peserta->email }})</label>
                        </div>
                        @endforeach
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Update Rapat</button>
                <a href="{{ route('rapat.index') }}" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>
@endsection
