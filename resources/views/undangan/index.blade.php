@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between mb-3">
        <h3>Data Undangan</h3>
        <a href="{{ route('undangan.create') }}" class="btn btn-primary">+ Kirim Undangan</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @elseif(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped m-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Peserta</th>
                        <th>Rapat</th>
                        <th>Status</th>
                        <th>Dikirim Pada</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($undangan as $no => $u)
                    <tr>
                        <td>{{ $no+1 }}</td>
                        <td>{{ $u->nama_peserta }}</td>
                        <td>{{ $u->judul_rapat }}</td>
                        <td>
                            <span class="badge 
                                {{ $u->status == 'terkirim' ? 'badge-primary' : 
                                   ($u->status == 'diterima' ? 'badge-success' : 'badge-info') }}">
                                {{ ucfirst($u->status) }}
                            </span>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($u->created_at)->format('d M Y H:i') }}</td>
                        <td>
                            {{-- Edit undangan, jika diperlukan --}}
                            {{-- <a href="{{ route('undangan.edit', $u->id) }}" class="btn btn-warning btn-sm">Edit</a> --}}
                            <form action="{{ route('undangan.destroy', $u->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus undangan ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                    @if($undangan->count() == 0)
                    <tr>
                        <td colspan="6" class="text-center">Belum ada undangan.</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
