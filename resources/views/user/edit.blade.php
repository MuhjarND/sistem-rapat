@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Edit User</h3>
    <div class="card">
        <div class="card-body">
            <form action="{{ route('user.update', $user->id) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Nama --}}
                <div class="form-group mb-3">
                    <label>Nama</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           required value="{{ old('name', $user->name) }}">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Jabatan --}}
                <div class="form-group mb-3">
                    <label>Jabatan</label>
                    <input type="text" name="jabatan" class="form-control @error('jabatan') is-invalid @enderror"
                           value="{{ old('jabatan', $user->jabatan) }}">
                    @error('jabatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Email --}}
                <div class="form-group mb-3">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                           required value="{{ old('email', $user->email) }}">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- No. HP --}}
                <div class="form-group mb-3">
                    <label>No. HP</label>
                    <input type="text" name="no_hp" class="form-control @error('no_hp') is-invalid @enderror"
                           value="{{ old('no_hp', $user->no_hp) }}" placeholder="08xxxxxxxxxx">
                    @error('no_hp') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Unit --}}
                <div class="form-group mb-3">
                    <label>Unit</label>
                    <select name="unit" class="form-control @error('unit') is-invalid @enderror" required>
                        <option value="">-- Pilih Unit --</option>
                        @foreach($daftar_unit as $opt)
                            <option value="{{ $opt }}" {{ (old('unit', $user->unit) == $opt) ? 'selected' : '' }}>
                                {{ ucfirst($opt) }}
                            </option>
                        @endforeach
                    </select>
                    @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Tingkatan (opsional) --}}
                <div class="form-group mb-3">
                <label>Tingkatan (opsional)</label>
                <select name="tingkatan" class="form-control @error('tingkatan') is-invalid @enderror">
                    <option value="">-- Tanpa tingkatan --</option>
                    @foreach($daftar_tingkatan as $t)
                        <option value="{{ $t }}" {{ (old('tingkatan', $user->tingkatan) == $t) ? 'selected' : '' }}>Tingkatan {{ $t }}</option>
                    @endforeach
                </select>
                @error('tingkatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <small class="form-text text-muted">Jika diisi, role user akan menjadi <b>approval</b> secara otomatis.</small>
                </div>
  
                <div class="form-group col-md-3">
                <label>Hirarki <small class="text-muted">(kecil = di atas)</small></label>
                <input type="number" name="hirarki" class="form-control"
                        value="{{ old('hirarki', $user->hirarki ?? null) }}" min="0" max="65535" step="1"
                        placeholder="mis. 0 untuk Pimpinan">
                </div>

                {{-- Role --}}
                <div class="form-group mb-3">
                    <label>Role</label>
                    <select name="role" class="form-control @error('role') is-invalid @enderror" required>
                        <option value="">-- Pilih Role --</option>
                        @foreach($daftar_role as $role)
                            <option value="{{ $role }}" {{ (old('role', $user->role) == $role) ? 'selected' : '' }}>
                                {{ ucfirst($role) }}
                            </option>
                        @endforeach
                    </select>
                    @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <hr>

                {{-- Password --}}
                <div class="form-group mb-3">
                    <label>Password (biarkan kosong jika tidak diubah)</label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Konfirmasi Password --}}
                <div class="form-group mb-4">
                    <label>Konfirmasi Password</label>
                    <input type="password" name="password_confirmation" class="form-control">
                </div>

                <button class="btn btn-primary">Update</button>
                <a href="{{ route('user.index') }}" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>
@endsection
