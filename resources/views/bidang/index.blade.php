@extends('layouts.app')
@section('title','Daftar Bidang')

@section('style')
<style>
  :root{
    --panel: rgba(255,255,255,.02);
    --border: rgba(226,232,240,.15);
    --muted: #9fb0cd;
    --text: #e5e7eb;
    --shadow: 0 12px 30px rgba(0,0,0,.25);
    --radius: 14px;
  }
  .table thead th{ text-align:center; vertical-align:middle; }
  .table td{ vertical-align: middle; }
  .table.no-hover tbody tr:hover { background: transparent !important; }

  /* card table */
  .card-table{
    background: var(--panel);
    border:1px solid var(--border);
    border-radius: var(--radius);
    overflow:hidden;
    box-shadow: var(--shadow);
  }
  .card-table .table{
    margin-bottom: 0;
  }
  .card-table .table thead th{
    background: rgba(15,23,42,.55);
    border-color: var(--border);
    color: #cfe1ff;
    letter-spacing:.3px;
  }
  .card-table .table tbody td{
    border-color: var(--border);
  }

  /* badge status */
  .badge-status{
    display:inline-flex; align-items:center; gap:6px;
    border-radius: 999px; padding:.3rem .6rem; font-weight:700; letter-spacing:.2px; font-size:.8rem;
  }
  .badge-green { background: rgba(34,197,94,.18); color:#d1fae5; border:1px solid rgba(34,197,94,.28); }
  .badge-slate { background: rgba(148,163,184,.18); color:#e2e8f0; border:1px solid rgba(148,163,184,.28); }

  /* count chip */
  .chip{
    display:inline-flex; align-items:center; gap:6px;
    background: rgba(255,255,255,.06);
    border:1px solid var(--border);
    padding:.25rem .55rem; border-radius: 999px; font-size:.85rem;
  }

  /* icon buttons */
  .btn-icon{
    width:36px; height:36px; border-radius:12px; display:inline-flex; align-items:center; justify-content:center;
    border:1px solid rgba(255,255,255,.14); color:#fff; transition:.15s; padding:0;
  }
  .btn-icon:hover{ filter: brightness(1.06); }
  .btn-cyan  { background: linear-gradient(180deg,#06b6d4,#0891b2); } /* lihat user */
  .btn-amber { background: linear-gradient(180deg,#f59e0b,#d97706); } /* edit */
  .btn-rose  { background: linear-gradient(180deg,#ef4444,#dc2626); } /* hapus */

  /* modal solid */
  .modal-solid .modal-content{
    background: rgba(15,23,42,.96);
    color: var(--text);
    border:1px solid var(--border);
  }
  .modal-solid .form-control, .modal-solid .custom-select{
    background: rgba(255,255,255,.06);
    border:1px solid var(--border);
    color:#fff;
  }
  .subtitle{ font-size:12px; color:var(--muted); }

  /* mobile */
  @media (max-width: 575.98px){
    .table thead{ display:none; }
    .table tbody tr{
      display:block; margin:10px 12px; border:1px solid var(--border);
      border-radius:12px; overflow:hidden; background: rgba(255,255,255,.02);
    }
    .table tbody td{
      display:block; width:100%; border:0 !important; border-bottom:1px solid var(--border) !important;
      padding:.75rem .95rem !important; text-align:left !important;
    }
    .table tbody td:last-child{ border-bottom:0 !important; }
    .table tbody td[data-label]::before{
      content: attr(data-label);
      display:block; font-size:.72rem; font-weight:800; letter-spacing:.2px; color:var(--muted);
      text-transform:uppercase; margin-bottom:6px;
    }
    .td-actions .btn-icon{ width:100%; height:38px; border-radius:10px; }
    .td-actions .d-inline-flex{ width:100%; gap:6px; }
    .td-actions .d-inline-flex .btn-icon{ flex:1; }
  }
</style>
@endsection

@section('content')
<div class="container">
  {{-- Header + actions --}}
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h3 class="mb-0">Daftar Bidang</h3>
    <div class="d-flex align-items-center">
      <form method="GET" class="d-none d-md-flex align-items-center mr-2">
        <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Cari bidang…"
               class="form-control form-control-sm mr-2" style="width:240px">
        <button class="btn btn-outline-light btn-sm">Cari</button>
      </form>
      <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalCreate">
        <i class="fas fa-plus mr-1"></i> Tambah Bidang
      </button>
    </div>
  </div>

  {{-- Alerts --}}
  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div>   @endif

  {{-- Table --}}
  <div class="card card-table">
    <div class="table-responsive">
      <table class="table table-sm no-hover">
        <thead>
          <tr class="text-center">
            <th style="width:60px;">#</th>
            <th>Nama</th>
            <th style="width:180px;">Singkatan</th>
            <th style="width:260px;">Keterangan</th>
            <th style="width:120px;">Status</th>
            <th style="width:110px;">Dipakai</th>
            <th style="width:190px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
        @forelse($rows as $i => $b)
          @php
            $rowNo   = ($rows->currentPage()-1)*$rows->perPage() + $i + 1;
            $active  = (int)($b->is_active ?? 1) === 1;
            $used    = (int)($b->users_count ?? $b->total_user ?? 0);
          @endphp
          <tr>
            <td class="text-center" data-label="#">{{ $rowNo }}</td>

            <td data-label="Nama">
              <strong>{{ $b->nama ?? $b->bidang_label ?? '-' }}</strong>
              @if(!empty($b->nama_induk))
                <div class="subtitle">Induk: {{ $b->nama_induk }}</div>
              @endif
            </td>

            <td class="text-center" data-label="Singkatan">
              {{ $b->singkatan ?? '—' }}
            </td>

            <td data-label="Keterangan">
              {{ $b->keterangan ?? '—' }}
            </td>

            <td class="text-center" data-label="Status">
              @if($active)
                <span class="badge-status badge-green">Aktif</span>
              @else
                <span class="badge-status badge-slate">Nonaktif</span>
              @endif
            </td>

            <td class="text-center" data-label="Dipakai">
              <span class="chip"><i class="fas fa-users"></i> {{ $used }}</span>
            </td>

            <td class="text-center td-actions" data-label="Aksi">
              <div class="d-inline-flex">
                {{-- Lihat user yang menggunakan bidang ini (filter ke user.index) --}}
                <a href="{{ route('user.index', ['bidang' => $b->nama ?? $b->bidang_label]) }}"
                   class="btn-icon btn-cyan mr-1" data-toggle="tooltip" title="Lihat User">
                  <i class="fas fa-users"></i>
                </a>

                {{-- Edit (modal) --}}
                <button type="button" class="btn-icon btn-amber mr-1 btn-edit"
                        data-id="{{ $b->id ?? '' }}"
                        data-nama="{{ $b->nama ?? $b->bidang_label ?? '' }}"
                        data-singkatan="{{ $b->singkatan ?? '' }}"
                        data-keterangan="{{ $b->keterangan ?? '' }}"
                        data-active="{{ (int)($b->is_active ?? 1) }}"
                        data-toggle="modal" data-target="#modalEdit"
                        title="Edit">
                  <i class="fas fa-edit"></i>
                </button>

                {{-- Hapus --}}
                @if(!empty($b->id))
                <form action="{{ route('bidang.destroy', $b->id) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Hapus bidang ini? User yang memakai bidang ini TIDAK dihapus.')">
                  @csrf @method('DELETE')
                  <button class="btn-icon btn-rose" title="Hapus">
                    <i class="fas fa-trash"></i>
                  </button>
                </form>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="text-center text-muted p-4">Belum ada data.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3 d-flex justify-content-center">
    {{ $rows->links('pagination::bootstrap-4') }}
  </div>
</div>

{{-- ===================== MODAL: CREATE ===================== --}}
<div class="modal fade modal-solid" id="modalCreate" tabindex="-1" aria-labelledby="modalCreateLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" action="{{ route('bidang.store') }}" method="POST">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title" id="modalCreateLabel">Tambah Bidang</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Nama Bidang <span class="text-danger">*</span></label>
          <input type="text" name="nama" class="form-control" required maxlength="120">
        </div>
        <div class="form-group">
          <label>Singkatan</label>
          <input type="text" name="singkatan" class="form-control" maxlength="40" placeholder="Opsional, mis. PTA PB">
        </div>
        <div class="form-group">
          <label>Keterangan</label>
          <textarea name="keterangan" class="form-control" rows="2" placeholder="Opsional"></textarea>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="is_active" class="custom-select">
            <option value="1" selected>Aktif</option>
            <option value="0">Nonaktif</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" type="submit">Simpan</button>
      </div>
    </form>
  </div>
</div>

{{-- ===================== MODAL: EDIT ===================== --}}
<div class="modal fade modal-solid" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formEdit" class="modal-content" method="POST">
      @csrf @method('PUT')
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditLabel">Edit Bidang</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Nama Bidang <span class="text-danger">*</span></label>
          <input type="text" name="nama" id="e_nama" class="form-control" required maxlength="120">
        </div>
        <div class="form-group">
          <label>Singkatan</label>
          <input type="text" name="singkatan" id="e_singkatan" class="form-control" maxlength="40">
        </div>
        <div class="form-group">
          <label>Keterangan</label>
          <textarea name="keterangan" id="e_keterangan" class="form-control" rows="2"></textarea>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="is_active" id="e_active" class="custom-select">
            <option value="1">Aktif</option>
            <option value="0">Nonaktif</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" type="submit">Simpan</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // Inject data ke modal edit
  document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', () => {
      const id    = btn.dataset.id;
      const nama  = btn.dataset.nama || '';
      const sing  = btn.dataset.singkatan || '';
      const ket   = btn.dataset.keterangan || '';
      const act   = btn.dataset.active || '1';

      document.getElementById('e_nama').value       = nama;
      document.getElementById('e_singkatan').value  = sing;
      document.getElementById('e_keterangan').value = ket;
      document.getElementById('e_active').value     = act;

      const form = document.getElementById('formEdit');
      form.action = "{{ url('/bidang') }}/" + id; // route('bidang.update', id)
    });
  });
</script>
@endpush
