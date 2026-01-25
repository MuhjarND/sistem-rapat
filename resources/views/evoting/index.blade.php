@extends('layouts.app')

@section('title', 'E-Voting')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">E-Voting</h3>
    <a href="{{ route('evoting.create') }}" class="btn btn-primary">
        <i class="fas fa-plus mr-1"></i> Buat E-Voting
    </a>
</div>

@if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
@if($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif

<div class="card">
    <div class="card-body data-scroll">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>Judul</th>
                    <th>Status</th>
                    <th>Item</th>
                    <th>Peserta</th>
                    <th>Sudah Voting</th>
                    <th>Dibuat Oleh</th>
                    <th>Aksi</th>
                </tr>
                </thead>
                <tbody>
                @forelse($evotings as $row)
                    <tr>
                        <td class="text-left">{{ $row->judul }}</td>
                        <td>
                            @if($row->status === 'open')
                                <span class="badge badge-success">Open</span>
                            @else
                                <span class="badge badge-secondary">Closed</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $row->item_count ?? 0 }}</td>
                        <td class="text-center">{{ $row->voter_count ?? 0 }}</td>
                        <td class="text-center">{{ $row->voted_count ?? 0 }}</td>
                        <td class="text-left">{{ $row->creator_name ?? '-' }}</td>
                        <td class="text-center">
                            <a href="{{ route('evoting.show', $row->id) }}" class="btn btn-sm btn-outline-light">
                                <i class="fas fa-eye mr-1"></i> Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">Belum ada e-voting.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
