@extends('layouts.app')

@section('content')
<div class="container">

  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Daftar User</h3>

    {{-- Jika datang dari Units (pick_unit), teruskan param saat klik Tambah User --}}
    @php
      $createUrl = isset($pickUnit) && $pickUnit
        ? route('user.create', ['pick_unit' => $pickUnit])
        : route('user.create');
    @endphp
    <a href="{{ $createUrl }}" class="btn btn-primary">+ Tambah User</a>
  </div>

  {{-- Alert sukses --}}
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  {{-- Info filter dari Units --}}
  @if(!empty($pickUnit) && !empty($unitName))
    <div class="alert alert-info py-2 mb-3">
      Menyaring berdasarkan unit: <b>{{ $unitName }}</b>.
      <a href="{{ route('user.index') }}" class="ml-2">Hapus filter</a>
    </div>
  @endif

  {{-- ================= Filter ================= --}}
  <form method="GET" action="{{ route('user.index') }}" class="card mb-3">
    <div class="card-body">
      <div class="form-row align-items-end">
        <div class="col-md-3 mb-2">
          <label class="mb-1 text-muted">Cari</label>
          <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control form-control-sm" placeholder="Nama / email / jabatan / no. HP">
        </div>

        <div class="col-md-3 mb-2">
          <label class="mb-1 text-muted">Unit</label>
          <select name="unit" class="custom-select custom-select-sm">
            <option value="">Semua Unit</option>
            @foreach(($daftar_unit ?? []) as $u)
              <option value="{{ $u }}" {{ ($unitName ?? '')===$u ? 'selected' : '' }}>
                {{ $u }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-md-2 mb-2">
          <label class="mb-1 text-muted">Role</label>
          <select name="role" class="custom-select custom-select-sm">
            @php $roles = ['', 'admin', 'operator', 'notulis', 'peserta', 'approval']; @endphp
            @foreach($roles as $r)
              <option value="{{ $r }}" {{ ($role ?? '')===$r ? 'selected' : '' }}>
                {{ $r==='' ? 'Semua' : ucfirst($r) }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-md-2 mb-2">
          <label class="mb-1 text-muted">Per Halaman</label>
          <select name="per_page" class="custom-select custom-select-sm">
            @foreach([10,12,15,25,50] as $pp)
              <option value="{{ $pp }}" {{ (int)($perPage ?? 12)===$pp ? 'selected' : '' }}>{{ $pp }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-2 mb-2">
          <label class="mb-1 d-none d-md-block">&nbsp;</label>
          <div class="d-flex">
            <button class="btn btn-primary btn-sm mr-2">
              <i class="fas fa-filter mr-1"></i> Terapkan
            </button>
            <a href="{{ route('user.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
          </div>
        </div>
      </div>

      {{-- pertahankan pick_unit ketika menerapkan filter --}}
      @if(!empty($pickUnit))
        <input type="hidden" name="pick_unit" value="{{ $pickUnit }}">
      @endif
    </div>
  </form>

  {{-- ================= Tabel ================= --}}
  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped table-sm mb-0">
          <thead>
            <tr class="text-center">
              <th style="width:60px">#</th>
              <th>Nama</th>
              <th style="width:200px;">Jabatan</th>
              <th style="width:200px;">Email</th>
              <th style="width:120px;">No. HP</th>
              <th style="width:160px;">Unit</th>
              <th style="width:100px;">Tingkatan</th>
              <th style="width:120px;">Role</th>
              <th style="width:80px;">Hirarki</th>
              <th style="width:140px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse($daftar_user as $i => $user)
              <tr>
                <td class="text-center">
                  {{ method_exists($daftar_user,'firstItem') ? $daftar_user->firstItem() + $i : ($i + 1) }}
                </td>
                <td>
                  <strong>{{ $user->name }}</strong>
                </td>
                <td>{{ $user->jabatan ?? '-' }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->no_hp ?? '-' }}</td>
                <td>{{ $user->unit ?? '-' }}</td>
                <td class="text-center">{{ $user->tingkatan ? 'T'.$user->tingkatan : '-' }}</td>
                <td class="text-center">{{ ucfirst($user->role) }}</td>
                <td class="text-center">{{ $user->hirarki ?? '-' }}</td>
                <td class="text-center">
                  <a href="{{ route('user.edit', $user->id) }}" class="btn btn-warning btn-sm">Edit</a>
                  <form action="{{ route('user.destroy', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus user ini?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger btn-sm">Hapus</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="10" class="text-center text-muted p-4">Belum ada user.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Pagination --}}
  @if(method_exists($daftar_user,'hasPages') && $daftar_user->hasPages())
    <div class="mt-3 d-flex justify-content-center">
      {{ $daftar_user->appends(request()->query())->links() }}
    </div>
  @endif

</div>
@endsection
