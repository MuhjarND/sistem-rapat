@extends('layouts.app')

@section('content')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Edit User</h3>

    @php
      // kalau datang dari Units -> "Pilih pengguna baru", kita jaga konteks baliknya
      $backUrl = request()->filled('pick_unit')
        ? route('user.index', ['unit' => request('pick_unit'), 'pick_unit' => request('pick_unit')])
        : route('user.index');
    @endphp

    <a href="{{ $backUrl }}" class="btn btn-secondary">← Kembali</a>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  <div class="card">
    <div class="card-body">
      <form action="{{ route('user.update', $user->id) }}" method="POST" autocomplete="off">
        @csrf
        @method('PUT')

        {{-- Nama --}}
        <div class="form-group mb-3">
          <label for="name">Nama <span class="text-danger">*</span></label>
          <input type="text" name="name" id="name"
                 class="form-control @error('name') is-invalid @enderror"
                 value="{{ old('name', $user->name) }}" required>
          @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- Jabatan --}}
        <div class="form-group mb-3">
          <label for="jabatan">Jabatan</label>
          <input type="text" name="jabatan" id="jabatan"
                 class="form-control @error('jabatan') is-invalid @enderror"
                 value="{{ old('jabatan', $user->jabatan) }}">
          @error('jabatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- Email --}}
        <div class="form-group mb-3">
          <label for="email">Email <span class="text-danger">*</span></label>
          <input type="email" name="email" id="email"
                 class="form-control @error('email') is-invalid @enderror"
                 value="{{ old('email', $user->email) }}" required>
          @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- No. HP --}}
        <div class="form-group mb-3">
          <label for="no_hp">No. HP</label>
          <input type="text" name="no_hp" id="no_hp"
                 class="form-control @error('no_hp') is-invalid @enderror"
                 value="{{ old('no_hp', $user->no_hp) }}" placeholder="08xxxxxxxxxx">
          @error('no_hp') <div class="invalid-feedback">{{ $message }}</div> @enderror
          <small class="form-text text-muted">Format: mulai dari 0, 10–14 digit. Contoh: 081234567890</small>
        </div>

        {{-- Unit (dinamis) --}}
        <div class="form-group mb-3">
          <label for="unit">Unit <span class="text-danger">*</span></label>
          @php
            // preselect: prioritas old(), lalu nilai user saat ini
            $selectedUnit = old('unit', $user->unit);
          @endphp
          <select name="unit" id="unit" class="form-control @error('unit') is-invalid @enderror" required>
            <option value="">-- Pilih Unit --</option>
            @foreach($daftar_unit as $opt)
              <option value="{{ $opt }}" {{ $selectedUnit == $opt ? 'selected' : '' }}>
                {{ ucfirst($opt) }}
              </option>
            @endforeach
          </select>
          @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror

          @if(Route::has('units.index'))
            <small class="form-text text-muted">
              Ingin menambah opsi unit? <a href="{{ route('units.index') }}" target="_blank">Kelola daftar unit</a>.
            </small>
          @endif
        </div>

        {{-- Tingkatan (opsional) --}}
        <div class="form-group mb-3">
          <label for="tingkatan">Tingkatan (opsional)</label>
          <select name="tingkatan" id="tingkatan" class="form-control @error('tingkatan') is-invalid @enderror">
            <option value="">-- Tanpa tingkatan --</option>
            @foreach($daftar_tingkatan as $t)
              <option value="{{ $t }}" {{ (string)old('tingkatan', (string)$user->tingkatan) === (string)$t ? 'selected' : '' }}>
                Tingkatan {{ $t }}
              </option>
            @endforeach
          </select>
          @error('tingkatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
          <small class="form-text text-muted">
            Jika diisi, role akan otomatis menjadi <b>approval</b> (logika diproses di backend).
          </small>
        </div>

        {{-- Hirarki --}}
        <div class="form-group mb-3">
          <label for="hirarki">Hirarki <small class="text-muted">(kecil = di atas)</small></label>
          <input type="number" name="hirarki" id="hirarki"
                 class="form-control @error('hirarki') is-invalid @enderror"
                 value="{{ old('hirarki', $user->hirarki) }}"
                 min="0" max="65535" step="1" placeholder="mis. 0 untuk Pimpinan">
          @error('hirarki') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- Role --}}
        <div class="form-group mb-3">
          <label for="role">Role <span class="text-danger">*</span></label>
          <select name="role" id="role" class="form-control @error('role') is-invalid @enderror" required>
            <option value="">-- Pilih Role --</option>
            @foreach($daftar_role as $role)
              <option value="{{ $role }}" {{ (old('role', $user->role) == $role) ? 'selected' : '' }}>
                {{ ucfirst($role) }}
              </option>
            @endforeach
          </select>
          @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
          <small class="form-text text-muted">
            Catatan: jika kamu memilih tingkatan di atas, role akan diset menjadi <b>approval</b> secara otomatis.
          </small>
        </div>

        <hr>

        {{-- Password --}}
        <div class="form-group mb-3">
          <label for="password">Password (biarkan kosong jika tidak diubah)</label>
          <input type="password" name="password" id="password"
                 class="form-control @error('password') is-invalid @enderror">
          @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- Konfirmasi Password --}}
        <div class="form-group mb-4">
          <label for="password_confirmation">Konfirmasi Password</label>
          <input type="password" name="password_confirmation" id="password_confirmation" class="form-control">
        </div>

        {{-- pertahankan pick_unit saat update agar sesudah save bisa balik sesuai konteks --}}
        @if(request()->filled('pick_unit'))
          <input type="hidden" name="pick_unit" value="{{ request('pick_unit') }}">
        @endif

        <div class="d-flex gap-2">
          <button class="btn btn-primary">Update</button>
          <a href="{{ $backUrl }}" class="btn btn-light">Batal</a>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
