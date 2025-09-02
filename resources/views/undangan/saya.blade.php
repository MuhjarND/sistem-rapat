@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Undangan Rapat Saya</h3>
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped m-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Judul Rapat</th>
                        <th>Tanggal</th>
                        <th>Tempat</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($undangan as $no => $u)
                    <tr>
                        <td>{{ $no+1 }}</td>
                        <td>{{ $u->judul }}</td>
                        <td>{{ \Carbon\Carbon::parse($u->tanggal)->format('d M Y') }}</td>
                        <td>{{ $u->tempat }}</td>
                        <td>
                            <span class="badge 
                                {{ $u->status == 'terkirim' ? 'badge-primary' : 
                                   ($u->status == 'diterima' ? 'badge-success' : 'badge-info') }}">
                                {{ ucfirst($u->status) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                    @if($undangan->count() == 0)
                    <tr>
                        <td colspan="5" class="text-center">Belum ada undangan.</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
