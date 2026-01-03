@extends('layouts.app')
@section('title','Daftar User')

@section('style')
<style>
  /* ===== Desktop table ===== */
  .table thead th{
    text-align:center;
    vertical-align:middle;
    background:rgba(79,70,229,.18);
    border-bottom:1px solid rgba(255,255,255,.12);
  }
  .table td{ vertical-align: middle; }
  .table tbody tr:nth-child(even){ background:rgba(255,255,255,.015); }
  .table tbody tr:hover{ background:rgba(79,70,229,.08); }

  .badge-chip{
    display:inline-block; padding:.15rem .55rem; border-radius:999px;
    background: rgba(255,255,255,.06); border:1px solid rgba(226,232,240,.15);
    font-weight:700; font-size:.8rem;
  }

  .btn-icon{
    width:34px; height:34px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center;
    border:1px solid rgba(255,255,255,.14); color:#fff; transition:.15s;
  }
  .btn-amber { background: linear-gradient(180deg,#f59e0b,#d97706); }
  .btn-rose  { background: linear-gradient(180deg,#ef4444,#dc2626); }
  .btn-cyan  { background: linear-gradient(180deg,#06b6d4,#0891b2); }
  .btn-icon:hover{ filter: brightness(1.06); }

  .card-filter{
    border-radius:16px;
    border:1px solid rgba(255,255,255,.12);
    background:linear-gradient(180deg,rgba(15,23,42,.9),rgba(11,18,41,.95));
    box-shadow:var(--shadow);
  }

  .summary-card{
    border-radius:14px;
    padding:14px 16px;
    background:linear-gradient(180deg,rgba(79,70,229,.22),rgba(14,165,233,.15));
    color:#fff;
    box-shadow:var(--shadow);
    border:1px solid rgba(255,255,255,.12);
  }
  .summary-card .value{font-size:1.8rem;font-weight:800;letter-spacing:.2px}
  .summary-card .label{font-size:.85rem;opacity:.85}

  /* ===== Mobile cards ===== */
  @media (max-width: 575.98px){
    .table-responsive{ border:0; }
    .table thead{ display:none; }
    .table tbody tr{
      display:block;
      background: rgba(255,255,255,.02);
      border:1px solid var(--border);
      border-radius:12px;
      margin:10px 12px;
      overflow:hidden;
    }
    .table tbody td{
      display:block;
      width:100%;
      border:0 !important;
      border-bottom:1px solid var(--border) !important;
      padding:.75rem .95rem !important;
      text-align:left !important;
    }
    .table tbody td:last-child{ border-bottom:0 !important; }
    .table tbody td[data-label]::before{
      content: attr(data-label);
      display:block;
      font-size:.72rem;
      font-weight:800;
      letter-spacing:.2px;
      color:#9fb0cd;
      text-transform:uppercase;
      margin-bottom:6px;
    }
    /* Action buttons: full width */
    td[data-label="Aksi"] .d-inline-flex{ width:100%; gap:6px; }
    td[data-label="Aksi"] .btn-icon{ flex:1; height:38px; border-radius:10px; }

    /* Filter row stacking nicely */
    .card .form-row > .col-md-3,
    .card .form-row > .col-md-2,
    .card .form-row > .col-md-1,
    .card .form-row > .col-md-12{
      width:100%;
    }
    .card .form-row .custom-select-sm,
    .card .form-row .form-control-sm{ height: calc(1.5em + .5rem + 2px); }
  }
</style>
@endsection

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
    <div class="d-flex align-items-center">
      <form action="{{ route('user.sendCredentialsAll') }}" method="POST" class="mr-2" onsubmit="return confirm('Kirim info login ke semua user sesuai filter?')">
        @csrf
        <input type="hidden" name="q" value="{{ $q ?? '' }}">
        <input type="hidden" name="role" value="{{ $role ?? '' }}">
        <input type="hidden" name="unit" value="{{ $unitName ?? '' }}">
        <input type="hidden" name="bidang" value="{{ $bidangName ?? '' }}">
        <button class="btn btn-outline-info">Kirim Login Semua</button>
      </form>
      <a href="{{ $createUrl }}" class="btn btn-primary">+ Tambah User</a>
    </div>
  </div>

  @php
    $totalUsers = method_exists($daftar_user, 'total') ? $daftar_user->total() : count($daftar_user);
    $totalUnit  = isset($daftar_unit) ? count($daftar_unit) : 0;
    $totalBidang= isset($daftar_bidang) ? count($daftar_bidang) : 0;
  @endphp

  {{-- Summary --}}
  <div class="row mb-3">
    <div class="col-md-4 mb-2">
      <div class="summary-card">
        <div class="label">Total Pengguna</div>
        <div class="value">{{ $totalUsers }}</div>
      </div>
    </div>
    <div class="col-md-4 mb-2">
      <div class="summary-card">
        <div class="label">Unit Terdaftar</div>
        <div class="value">{{ $totalUnit }}</div>
      </div>
    </div>
    <div class="col-md-4 mb-2">
      <div class="summary-card">
        <div class="label">Bidang Terdaftar</div>
        <div class="value">{{ $totalBidang }}</div>
      </div>
    </div>
  </div>

  {{-- Alert sukses --}}
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  {{-- Info filter dari Units --}}
  @if(!empty($pickUnit) && !empty($unitName))
    <div class="alert alert-info py-2 mb-3">
      Menyaring berdasarkan unit: <b>{{ $unitName }}</b>.
      <a href="{{ route('user.index') }}" class="ml-2">Hapus filter</a>
    </div>
  @endif

  {{-- ================= Filter ================= --}}
  <form method="GET" action="{{ route('user.index') }}" class="card card-filter mb-3">
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

        {{-- === Filter Bidang (baru) === --}}
        <div class="col-md-3 mb-2">
          <label class="mb-1 text-muted">Bidang</label>
          <select name="bidang" class="custom-select custom-select-sm">
            <option value="">Semua Bidang</option>
            @foreach(($daftar_bidang ?? []) as $b)
              <option value="{{ $b }}" {{ ($bidangName ?? '')===$b ? 'selected' : '' }}>
                {{ $b }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-md-2 mb-2">
          <label class="mb-1 text-muted">Role</label>
          <select name="role" class="custom-select custom-select-sm">
            @php $roles = ['', 'admin', 'operator', 'notulis', 'peserta', 'approval', 'protokoler']; @endphp
            @foreach($roles as $r)
              <option value="{{ $r }}" {{ ($role ?? '')===$r ? 'selected' : '' }}>
                {{ $r==='' ? 'Semua' : ucfirst($r) }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-md-1 mb-2">
          <label class="mb-1 d-block text-muted">&nbsp;</label>
          <button class="btn btn-primary btn-sm btn-block">Filter</button>
        </div>
      </div>
    </div>
  </form>

  {{-- ================= Tabel / Mobile Cards ================= --}}
  <div class="card card-filter">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Hasil Pencarian</h5>
      <small class="text-muted">{{ $totalUsers }} pengguna ditemukan</small>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped table-sm mb-0">
          <thead>
            <tr class="text-center">
              <th style="width:60px">#</th>
              <th>Nama</th>
              <th style="width:240px;">Jabatan & Keterangan</th>
              <th style="width:240px;">Kontak</th>
              <th style="width:240px;">Unit & Bidang</th>
              <th style="width:100px;">Tingkatan</th>
              <th style="width:120px;">Role</th>
              <th style="width:80px;">Hirarki</th>
              <th style="width:140px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse($daftar_user as $i => $user)
              <tr>
                <td class="text-center" data-label="#">
                  {{ method_exists($daftar_user,'firstItem') ? $daftar_user->firstItem() + $i : ($i + 1) }}
                </td>

                <td data-label="Nama">
                  <strong>{{ $user->name }}</strong>
                </td>

                <td data-label="Jabatan & Keterangan">
                  <div><strong>{{ $user->jabatan_ref ?? $user->jabatan ?? '-' }}</strong></div>
                  @if(!empty($user->jabatan_keterangan))
                    <div class="text-muted small">Ket: {{ $user->jabatan_keterangan }}</div>
                  @endif
                </td>

                <td data-label="Kontak">
                  <div>{{ $user->email }}</div>
                  <div class="text-muted small">{{ $user->no_hp ?? 'No HP: -' }}</div>
                </td>

                <td data-label="Unit & Bidang">
                  <div>{{ $user->unit ?? '-' }}</div>
                  <div class="text-muted small">{{ $user->bidang ?: 'Tanpa bidang' }}</div>
                </td>

                <td class="text-center" data-label="Tingkatan">{{ $user->tingkatan ? 'T'.$user->tingkatan : '-' }}</td>

                <td class="text-center" data-label="Role">{{ ucfirst($user->role) }}</td>

                <td class="text-center" data-label="Hirarki">{{ $user->hirarki ?? '-' }}</td>

                <td class="text-center" data-label="Aksi">
                  <div class="d-inline-flex">
                    <a href="{{ route('user.edit', $user->id) }}" class="btn-icon btn-amber mr-1" title="Edit">
                      <i class="fas fa-edit"></i>
                    </a>
                    <form action="{{ route('user.sendCredentials', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Kirim info login ke user ini?')">
                      @csrf
                      <button class="btn-icon btn-cyan mr-1" title="Kirim Login">
                        <i class="fas fa-paper-plane"></i>
                      </button>
                    </form>
                    <form action="{{ route('user.destroy', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus user ini?')">
                      @csrf @method('DELETE')
                      <button class="btn-icon btn-rose" title="Hapus">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="text-center text-muted p-4">Belum ada user.</td>
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
