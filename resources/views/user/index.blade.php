@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between mb-3">
        <h3>Daftar User</h3>
        <a href="{{ route('user.create') }}" class="btn btn-primary">+ Tambah User</a>
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
                        <th>Email</th>
                        <th>No. HP</th>     {{-- baru --}}
                        <th>Unit</th>       {{-- baru --}}
                        <th>Role</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($daftar_user as $no => $user)
                    <tr>
                        <td>{{ $no + 1 }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->jabatan ?? '-' }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->no_hp ?? '-' }}</td> {{-- tampilkan no_hp --}}
                        <td class="text-capitalize">{{ $user->unit ?? '-' }}</td> {{-- tampilkan unit --}}
                        <td>{{ ucfirst($user->role) }}</td>
                        <td>
                            <a href="{{ route('user.edit', $user->id) }}" class="btn btn-warning btn-sm">Edit</a>
                            <form action="{{ route('user.destroy', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus user ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center">Belum ada user.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
