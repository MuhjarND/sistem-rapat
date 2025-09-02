@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Kirim Undangan Rapat</h3>
    <div class="card">
        <div class="card-body">
            <form action="{{ route('undangan.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label>Pilih Rapat</label>
                    <select name="id_rapat" class="form-control" required>
                        <option value="">-- Pilih --</option>
                        @foreach($rapat as $r)
                        <option value="{{ $r->id }}">{{ $r->judul }} ({{ \Carbon\Carbon::parse($r->tanggal)->format('d M Y') }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Pilih Peserta</label>
                    <select name="id_user" class="form-control" required>
                        <option value="">-- Pilih --</option>
                        @foreach($peserta as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->email }})</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-primary">Kirim Undangan</button>
                <a href="{{ route('undangan.index') }}" class="btn btn-secondary">Kembali</a>
            </form>
        </div>
    </div>
</div>
@endsection
