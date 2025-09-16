@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between mb-3">
        <h3>Tambah User</h3>
        <a href="{{ route('user.index') }}" class="btn btn-secondary">← Kembali</a>
    </div>

    {{-- Notifikasi sukses (jika ada redirect balik ke form, biasanya tidak perlu) --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('user.store') }}" method="POST">
                @csrf

                {{-- Nama --}}
                <div class="form-group mb-3">
                    <label for="name">Nama <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name') }}" maxlength="100" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Jabatan --}}
                <div class="form-group mb-3">
                    <label for="jabatan">Jabatan</label>
                    <input type="text" name="jabatan" id="jabatan" class="form-control @error('jabatan') is-invalid @enderror"
                           value="{{ old('jabatan') }}" maxlength="100" placeholder="Opsional">
                    @error('jabatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Email --}}
                <div class="form-group mb-3">
                    <label for="email">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email') }}" required>
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- No. HP --}}
                <div class="form-group mb-3">
                    <label for="no_hp">No. HP</label>
                    <input type="text" name="no_hp" id="no_hp" class="form-control @error('no_hp') is-invalid @enderror"
                           value="{{ old('no_hp') }}" placeholder="08xxxxxxxxxx">
                    @error('no_hp') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <small class="form-text text-muted">Format: mulai dari 0, 10–14 digit. Contoh: 081234567890</small>
                </div>

                {{-- Unit --}}
                <div class="form-group mb-3">
                    <label for="unit">Unit <span class="text-danger">*</span></label>
                    <select name="unit" id="unit" class="form-control @error('unit') is-invalid @enderror" required>
                        @foreach($daftar_unit as $opt)
                            <option value="{{ $opt }}" {{ old('unit', 'kesekretariatan') === $opt ? 'selected' : '' }}>
                                {{ ucfirst($opt) }}
                            </option>
                        @endforeach
                    </select>
                    @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Role --}}
                <div class="form-group mb-3">
                    <label for="role">Role <span class="text-danger">*</span></label>
                    <select name="role" id="role" class="form-control @error('role') is-invalid @enderror" required>
                        @foreach($daftar_role as $r)
                            <option value="{{ $r }}" {{ old('role') === $r ? 'selected' : '' }}>
                                {{ ucfirst($r) }}
                            </option>
                        @endforeach
                    </select>
                    @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Password --}}
                <div class="form-group mb-3">
                    <label for="password">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" id="password"
                           class="form-control @error('password') is-invalid @enderror" required>
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <small class="form-text text-muted">Minimal 6 karakter.</small>
                </div>

                {{-- Konfirmasi Password --}}
                <div class="form-group mb-4">
                    <label for="password_confirmation">Konfirmasi Password <span class="text-danger">*</span></label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                           class="form-control" required>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="{{ route('user.index') }}" class="btn btn-light">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
