@extends('layouts.app')
@section('title','Unit')

@push('style')
<style>
  .table thead th{ text-align:center; vertical-align:middle; }
  .table td{ vertical-align: middle; }
  .table.no-hover tbody tr:hover { background: transparent !important; }
  .badge{ font-weight: 700; }

  .btn-icon{
    width:34px;height:34px;border-radius:10px;
    display:inline-flex;align-items:center;justify-content:center;
    border:1px solid rgba(255,255,255,.14); color:#fff; transition:.15s ease; padding:0;
  }
  .btn-amber { background: linear-gradient(180deg,#f59e0b,#d97706); }  /* edit */
  .btn-rose  { background: linear-gradient(180deg,#ef4444,#dc2626); }  /* delete */
  .btn-cyan  { background: linear-gradient(180deg,#06b6d4,#0891b2); }  /* assign */
  .btn-icon:hover{ filter:brightness(1.06); }

  .filter-tight .form-group{ margin-bottom:.5rem; }
  .filter-tight label{ margin-bottom:.25rem; font-weight:600; color:#dbe7ff; font-size:.85rem; }

  .pill{
    display:inline-flex; align-items:center; gap:.35rem;
    padding:.22rem .55rem; border-radius:999px; font-size:.78rem; font-weight:800;
    background:rgba(255,255,255,.06); border:1px solid rgba(226,232,240,.15);
  }
</style>
@endpush

@section('content')
<div class="container">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Unit</h3>
    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalTambah">
      <i class="fas fa-plus mr-1"></i> Tambah Unit
    </button>
  </div>

  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div>   @endif

  {{-- Filter --}}
  <form method="GET" action="{{ route('units.index') }}" class="card mb-3 filter-tight">
    <div class="card-body">
      <div class="form-row align-items-end">
        <div class="form-group col-md-4">
          <label>Cari</label>
          <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control form-control-sm" placeholder="Nama / Singkatan / Keterangan">
        </div>
        <div class="form-group col-md-3">
          <label>Status</label>
          <select name="status" class="custom-select custom-select-sm">
            <option value="">Semua</option>
            <option value="1" {{ ($status==='1')?'selected':'' }}>Aktif</option>
            <option value="0" {{ ($status==='0')?'selected':'' }}>Nonaktif</option>
          </select>
        </div>
        <div class="form-group col-md-3">
          <label>Baris/hal</label>
          <select name="per_page" class="custom-select custom-select-sm">
            @foreach([6,12,20,30] as $pp)
              <option value="{{ $pp }}" {{ ($perPage ?? 12)==$pp?'selected':'' }}>{{ $pp }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-2">
          <label>&nbsp;</label>
          <div>
            <button class="btn btn-primary btn-sm mr-2"><i class="fas fa-filter mr-1"></i> Terapkan</button>
            <a href="{{ route('units.index') }}" class="btn btn-outline-light btn-sm">Reset</a>
          </div>
        </div>
      </div>
    </div>
  </form>

  {{-- Tabel --}}
  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead>
            <tr class="text-center">
              <th style="width:60px">#</th>
              <th>Nama</th>
              <th style="width:160px;">Singkatan</th>
              <th>Keterangan</th>
              <th style="width:120px;">Status</th>
              <th style="width:140px;">Dipakai</th>
              <th style="width:220px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
          @forelse($units as $i => $u)
            <tr>
              <td class="text-center">{{ ($units->currentPage()-1)*$units->perPage() + $i + 1 }}</td>
              <td>
                <strong>{{ $u->nama }}</strong>
              </td>
              <td class="text-center">{{ $u->singkatan ?? '—' }}</td>
              <td>{{ $u->keterangan ?? '—' }}</td>
              <td class="text-center">
                @if($u->is_active)
                  <span class="badge badge-success">Aktif</span>
                @else
                  <span class="badge badge-secondary">Nonaktif</span>
                @endif
              </td>
              <td class="text-center">
                <span class="pill"><i class="fas fa-users"></i> {{ (int)$u->jml_pengguna }}</span>
              </td>
              <td class="text-center">
                <div class="d-inline-flex">
                  {{-- Tetapkan ke Pengguna --}}
                  <a href="{{ route('user.index', ['pick_unit' => $u->id]) }}"
                     class="btn-icon btn-cyan mr-1" title="Tetapkan ke Pengguna">
                    <i class="fas fa-users-cog"></i>
                  </a>

                  {{-- Edit --}}
                  <button type="button" class="btn-icon btn-amber mr-1"
                          data-toggle="modal" data-target="#modalEdit-{{ $u->id }}"
                          title="Edit">
                    <i class="fas fa-edit"></i>
                  </button>

                  {{-- Hapus --}}
                  <form action="{{ route('units.destroy', $u->id) }}" method="POST"
                        onsubmit="return confirm('Hapus unit ini?')" class="d-inline">
                    @csrf @method('DELETE')
                    <button class="btn-icon btn-rose" title="Hapus">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>

            {{-- Modal Edit --}}
            <div class="modal fade" id="modalEdit-{{ $u->id }}" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog">
                <form class="modal-content modal-solid" action="{{ route('units.update', $u->id) }}" method="POST">
                  @csrf
                  @method('PUT')
                  <div class="modal-header">
                    <h5 class="modal-title">Edit Unit</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                  </div>
                  <div class="modal-body">
                    <div class="form-group">
                      <label>Nama</label>
                      <input type="text" name="nama" class="form-control" value="{{ $u->nama }}" required>
                    </div>
                    <div class="form-group">
                      <label>Singkatan <span class="text-muted">(opsional)</span></label>
                      <input type="text" name="singkatan" class="form-control" value="{{ $u->singkatan }}">
                    </div>
                    <div class="form-group">
                      <label>Keterangan <span class="text-muted">(opsional)</span></label>
                      <textarea name="keterangan" class="form-control" rows="3">{{ $u->keterangan }}</textarea>
                    </div>
                    <div class="form-group">
                      <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="sw-{{ $u->id }}" name="is_active" value="1" {{ $u->is_active ? 'checked' : '' }}>
                        <label class="custom-control-label" for="sw-{{ $u->id }}">Aktifkan</label>
                      </div>
                    </div>
                  </div>
                  <div class="modal-footer">
                    {{-- Opsional: simpan & langsung tetapkan --}}
                    <button class="btn btn-outline-light" name="go_assign" value="0">Simpan</button>
                    <button class="btn btn-primary" name="go_assign" value="1">
                      Simpan & Tetapkan ke Pengguna
                    </button>
                  </div>
                </form>
              </div>
            </div>
          @empty
            <tr><td colspan="7" class="text-center text-muted p-4">Belum ada unit.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Pagination --}}
  <div class="mt-3">
    {{ $units->links('pagination::bootstrap-4') }}
  </div>
</div>

{{-- Modal Tambah --}}
<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content modal-solid" action="{{ route('units.store') }}" method="POST">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Tambah Unit</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        @if($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0">
              @foreach($errors->all() as $e)
                <li>{{ $e }}</li>
              @endforeach
            </ul>
          </div>
        @endif
        <div class="form-group">
          <label>Nama</label>
          <input type="text" name="nama" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Singkatan <span class="text-muted">(opsional)</span></label>
          <input type="text" name="singkatan" class="form-control">
        </div>
        <div class="form-group">
          <label>Keterangan <span class="text-muted">(opsional)</span></label>
          <textarea name="keterangan" class="form-control" rows="3"></textarea>
        </div>
        <div class="form-group">
          <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="sw-add" name="is_active" value="1" checked>
            <label class="custom-control-label" for="sw-add">Aktifkan</label>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        {{-- Opsional: simpan & langsung tetapkan --}}
        <button class="btn btn-outline-light" name="go_assign" value="0">Simpan</button>
        <button class="btn btn-primary" name="go_assign" value="1">Simpan & Tetapkan ke Pengguna</button>
      </div>
    </form>
  </div>
</div>
@endsection
