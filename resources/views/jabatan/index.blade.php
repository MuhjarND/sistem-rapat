@extends('layouts.app')
@section('title','Daftar Jabatan')

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
  .card-table{
    background: var(--panel);
    border:1px solid var(--border);
    border-radius: var(--radius);
    overflow:hidden;
    box-shadow: var(--shadow);
  }
  .card-table .table{ margin-bottom:0; }
  .card-table thead th{
    background: rgba(15,23,42,.55);
    border-color: var(--border);
    color:#cfe1ff;
    letter-spacing:.3px;
    text-align:center;
  }
  .card-table tbody td{ border-color: var(--border); vertical-align: middle; }
  .badge-status{ padding:.2rem .5rem; border-radius:999px; font-size:.8rem; }
  .badge-active{ background:#ecfdf3; color:#166534; }
  .badge-inactive{ background:#fef2f2; color:#991b1b; }
</style>
@endsection

@section('content')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Daftar Jabatan</h3>
    <button class="btn btn-primary" data-toggle="modal" data-target="#modalCreate">+ Tambah Jabatan</button>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  <form method="GET" class="card mb-3">
    <div class="card-body">
      <div class="form-row align-items-end">
        <div class="col-md-4 mb-2">
          <label class="mb-1 text-muted">Cari</label>
          <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control form-control-sm" placeholder="Nama / kategori / keterangan">
        </div>
        <div class="col-md-2 mb-2">
          <label class="mb-1 d-block text-muted">&nbsp;</label>
          <button class="btn btn-primary btn-sm">Filter</button>
        </div>
      </div>
    </div>
  </form>

  <div class="card-table">
    <div class="table-responsive">
      <table class="table table-striped table-sm mb-0">
        <thead>
          <tr>
            <th style="width:60px">#</th>
            <th>Nama</th>
            <th style="width:180px;">Kategori</th>
            <th style="width:260px;">Keterangan</th>
            <th style="width:120px;">Status</th>
            <th style="width:120px;">Dipakai</th>
            <th style="width:140px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $i => $row)
            <tr>
              <td class="text-center">{{ method_exists($rows,'firstItem') ? $rows->firstItem() + $i : $i+1 }}</td>
              <td><strong>{{ $row->nama }}</strong></td>
              <td>{{ $row->kategori ?: '-' }}</td>
              <td>{{ $row->keterangan ?: '-' }}</td>
              <td class="text-center">
                @if($row->is_active)
                  <span class="badge badge-status badge-active">Aktif</span>
                @else
                  <span class="badge badge-status badge-inactive">Non-aktif</span>
                @endif
              </td>
              <td class="text-center">{{ $row->users_count }}</td>
              <td class="text-center">
                <div class="btn-group btn-group-sm">
                  <button class="btn btn-secondary btn-edit"
                          data-id="{{ $row->id }}"
                          data-nama="{{ $row->nama }}"
                          data-kategori="{{ $row->kategori }}"
                          data-keterangan="{{ $row->keterangan }}"
                          data-active="{{ $row->is_active }}"
                          data-toggle="modal" data-target="#modalEdit">
                    Edit
                  </button>
                  <form action="{{ route('jabatan.destroy', $row->id) }}" method="POST" onsubmit="return confirm('Hapus jabatan ini?');">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger">Hapus</button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center text-muted py-4">Belum ada data.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">
    {{ $rows->links() }}
  </div>
</div>

{{-- Modal Create --}}
<div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="{{ route('jabatan.store') }}">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Tambah Jabatan</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Nama <span class="text-danger">*</span></label>
          <input type="text" name="nama" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Kategori</label>
          <input type="text" name="kategori" class="form-control" placeholder="Opsional, mis. Struktural/Fungsional">
        </div>
        <div class="form-group">
          <label>Keterangan</label>
          <textarea name="keterangan" class="form-control" rows="2" placeholder="Deskripsi jabatan (opsional)"></textarea>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="is_active" class="form-control">
            <option value="1" selected>Aktif</option>
            <option value="0">Non-aktif</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

{{-- Modal Edit --}}
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" id="formEdit">
      @csrf @method('PUT')
      <div class="modal-header">
        <h5 class="modal-title">Edit Jabatan</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Nama <span class="text-danger">*</span></label>
          <input type="text" name="nama" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Kategori</label>
          <input type="text" name="kategori" class="form-control">
        </div>
        <div class="form-group">
          <label>Keterangan</label>
          <textarea name="keterangan" class="form-control" rows="2"></textarea>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="is_active" class="form-control">
            <option value="1">Aktif</option>
            <option value="0">Non-aktif</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
  (function(){
    var editButtons = document.querySelectorAll('.btn-edit');
    var formEdit = document.getElementById('formEdit');
    if(!editButtons.length || !formEdit) return;

    editButtons.forEach(function(btn){
      btn.addEventListener('click', function(){
        var id    = this.getAttribute('data-id');
        formEdit.action = "{{ url('jabatan') }}/" + id;
        formEdit.querySelector('input[name="nama"]').value = this.getAttribute('data-nama') || '';
        formEdit.querySelector('input[name="kategori"]').value = this.getAttribute('data-kategori') || '';
        formEdit.querySelector('textarea[name="keterangan"]').value = this.getAttribute('data-keterangan') || '';
        formEdit.querySelector('select[name="is_active"]').value = this.getAttribute('data-active') || '1';
      });
    });
  })();
</script>
@endpush
