@extends('layouts.app')
@section('title','Arsip Laporan')

@section('style')
<style>
  .table thead th{ text-align:center; vertical-align:middle; }
  .table td{ vertical-align: middle; }

  /* file pill lebih ramping */
  .pill-file{
    display:inline-block; padding:.35rem .6rem; border-radius:999px;
    background: rgba(255,255,255,.06); border:1px solid rgba(226,232,240,.15);
    font-size:.8rem; max-width: 220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; cursor:pointer;
  }

  .btn-icon{
    width:34px; height:34px; border-radius:999px; display:inline-flex; align-items:center; justify-content:center;
    border:1px solid rgba(255,255,255,.18); color:#fff; transition:.15s; padding:0; background: rgba(255,255,255,.06);
  }
  .btn-icon:hover{ filter: brightness(1.06); }
  .btn-teal   { background: linear-gradient(180deg,#14b8a6,#0d9488); }  /* Unarchive */
  .btn-cyan   { background: linear-gradient(180deg,#06b6d4,#0891b2); }  /* Download */
  .btn-amber  { background: linear-gradient(180deg,#f59e0b,#d97706); }  /* Edit */
  .btn-rose   { background: linear-gradient(180deg,#ef4444,#dc2626); }  /* Delete */

  .filter-tight .form-group{ margin-bottom:.5rem; }
  .filter-tight label{ margin-bottom:.25rem; font-weight:600; color:#dbe7ff; font-size:.85rem; }

  .table.no-hover tbody tr:hover { background: transparent !important; }
  .subtitle{ font-size:12px; color:#9fb0cd; }

  /* ===== Lebar kolom (desktop) */
  th.col-judul{ width:34%; } th.col-kategori{ width:260px; } th.col-rapat{ width:260px; }
  th.col-tanggal{ width:140px; } th.col-namafile{ width:240px; } th.col-aksi{ width:190px; }
  td.col-judul{ width:34%; } td.col-kategori{ width:260px; } td.col-rapat{ width:260px; }
  td.col-tanggal{ width:140px; } td.col-namafile{ width:240px; } td.col-aksi{ width:190px; }

  /* ===== Pagination (dark) */
  .pagination { margin-bottom: 0; }
  .page-item .page-link{
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(226,232,240,.15);
    color: var(--text);
  }
  .page-item .page-link:focus{ box-shadow: none; }
  .page-item.active .page-link{
    background: linear-gradient(180deg, var(--primary), var(--primary-700));
    border-color: transparent;
    color: #fff;
  }
  .page-item.disabled .page-link{ color: var(--muted); }

  /* ===== Modal Preview */
  .modal-preview .modal-dialog { max-width: 980px; }
  .modal-preview .modal-body { padding: 0; background: #0b1220; }
  #previewIframe { width: 100%; height: 75vh; border: 0; display:none; }
  #previewImg    { max-width: 100%; max-height: 75vh; display: none; margin: 0 auto; }
  #previewFallback { padding: 1rem; display: none; }
  .spinner-preview{ display:flex; align-items:center; justify-content:center; height:75vh; }

  /* ====== Mobile cards ====== */
  @media (max-width: 575.98px){
    /* Stack filter fields nicely */
    .filter-tight .form-row > .form-group{ width:100%; }
    .filter-tight .d-flex{ flex-direction:column; }
    .filter-tight .d-flex .btn{ width:100%; margin:0 0 .5rem 0; }

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
    /* action buttons: full width */
    .col-aksi .btn-icon{ width:100%; height:38px; border-radius:10px; }
    .col-aksi .d-inline-flex{ width:100%; gap:6px; }
    .col-aksi .d-inline-flex .btn-icon{ flex:1; }
    /* file pill grows on mobile */
    .pill-file{ max-width: 100%; }
  }
</style>
@endsection

@section('content')
<div class="container">

  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Arsip Laporan</h3>
    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalUploadArsip">
      <i class="fas fa-upload mr-1"></i> Upload ke Arsip
    </button>
  </div>

  {{-- ALERT --}}
  @if(session('ok'))      <div class="alert alert-success">{{ session('ok') }}</div> @endif
  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div>   @endif

  {{-- FILTER --}}
  <form class="card mb-3 filter-tight" method="GET" action="{{ route('laporan.arsip') }}">
    <div class="card-body">
      <div class="form-row align-items-end">
        <div class="form-group col-md-2">
          <label>Dari</label>
          <input type="date" name="dari" value="{{ $filter['dari'] ?? '' }}" class="form-control form-control-sm">
        </div>
        <div class="form-group col-md-2">
          <label>Sampai</label>
          <input type="date" name="sampai" value="{{ $filter['sampai'] ?? '' }}" class="form-control form-control-sm">
        </div>
        <div class="form-group col-md-3">
          <label>Kategori</label>
          <select name="id_kat" class="custom-select custom-select-sm">
            <option value="">Semua</option>
            @foreach($kategori as $k)
              <option value="{{ $k->id }}" {{ ($filter['id_kat']??'')==$k->id ? 'selected':'' }}>{{ $k->nama }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-3">
          <label>Rapat</label>
          <select name="id_rapat" class="custom-select custom-select-sm">
            <option value="">Semua</option>
            @foreach($rapatList as $r)
              <option value="{{ $r->id }}" {{ ($filter['id_rapat']??'')==$r->id ? 'selected':'' }}>
                {{ \Illuminate\Support\Str::limit($r->judul,32) }} — {{ \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-2">
          <label>Cari</label>
          <input type="text" name="qsearch" value="{{ $filter['qsearch'] ?? '' }}" class="form-control form-control-sm" placeholder="Judul/Ket/File">
        </div>
      </div>
      <div class="d-flex">
        <button class="btn btn-primary btn-sm"><i class="fas fa-filter mr-1"></i> Terapkan</button>
        <a href="{{ route('laporan.arsip') }}" class="btn btn-outline-light btn-sm ml-2">Reset</a>
      </div>
    </div>
  </form>

  {{-- TABEL ARSIP --}}
  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm mb-0 no-hover">
          <thead>
            <tr class="text-center">
              <th style="width:54px;">#</th>
              <th class="col-judul">Judul</th>
              <th class="col-kategori">Kategori</th>
              <th class="col-rapat">Rapat</th>
              <th class="col-tanggal">Tgl. Laporan</th>
              <th class="col-namafile">Nama File</th>
              <th class="col-aksi">Aksi</th>
            </tr>
          </thead>
          <tbody>
          @forelse ($uploads as $i => $u)
            @php $rowNo = ($uploads->currentPage()-1) * $uploads->perPage() + $i + 1; @endphp
            <tr>
              <td class="text-center" data-label="#"> {{ $rowNo }} </td>

              <td class="col-judul" data-label="Judul">
                <strong>{{ $u->judul }}</strong>
                @if($u->keterangan)
                  <div class="subtitle">{{ $u->keterangan }}</div>
                @endif
              </td>

              <td class="col-kategori" data-label="Kategori">{{ $u->nama_kategori ?? '-' }}</td>

              <td class="col-rapat" data-label="Rapat">
                @if($u->judul_rapat)
                  {{ \Illuminate\Support\Str::limit($u->judul_rapat,42) }}
                @else
                  -
                @endif
              </td>

              <td class="col-tanggal" data-label="Tgl. Laporan">
                @php $tgl = $u->tanggal_laporan ?: $u->created_at; @endphp
                {{ \Carbon\Carbon::parse($tgl)->format('d/m/Y') }}
              </td>

              <td class="col-namafile" data-label="Nama File">
                <a href="#"
                   class="pill-file preview-link"
                   title="Preview: {{ $u->file_name }}"
                   data-url="{{ route('laporan.file.preview',$u->id) }}?live=1"
                   data-mime="{{ $u->mime ?? 'application/octet-stream' }}"
                   data-filename="{{ $u->file_name }}">
                  {{ $u->file_name }}
                </a>
              </td>

              <td class="text-center col-aksi" data-label="Aksi">
                <div class="d-inline-flex">
                  <form action="{{ route('laporan.file.unarchive',$u->id) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('Pulihkan laporan ini dari arsip?')">
                    @csrf
                    <button class="btn-icon btn-teal mr-1" data-toggle="tooltip" title="Pulihkan dari Arsip">
                      <i class="fas fa-undo-alt"></i>
                    </button>
                  </form>

                  <a href="{{ route('laporan.file.download',$u->id) }}"
                     class="btn-icon btn-cyan mr-1" data-toggle="tooltip" title="Download">
                    <i class="fas fa-download"></i>
                  </a>

                  <button type="button"
                          class="btn-icon btn-amber mr-1 btn-edit"
                          data-id="{{ $u->id }}"
                          data-judul="{{ e($u->judul) }}"
                          data-id_kategori="{{ $u->id_kategori }}"
                          data-id_rapat="{{ $u->id_rapat }}"
                          data-tanggal="{{ $u->tanggal_laporan ? \Carbon\Carbon::parse($u->tanggal_laporan)->format('Y-m-d') : '' }}"
                          data-keterangan="{{ e($u->keterangan ?? '') }}"
                          data-toggle="modal" data-target="#modalEdit"
                          title="Edit">
                    <i class="fas fa-edit"></i>
                  </button>

                  <form action="{{ route('laporan.file.destroy',$u->id) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('Hapus lampiran ini secara permanen?')">
                    @csrf @method('DELETE')
                    <button class="btn-icon btn-rose" title="Hapus">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted p-4">Belum ada unggahan di arsip.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Pagination --}}
  @if ($uploads->hasPages())
    <div class="mt-3 d-flex justify-content-center">
      {{ $uploads->appends(request()->query())->links('pagination::bootstrap-4') }}
    </div>
  @endif
</div>

{{-- MODAL: PREVIEW FILE --}}
<div class="modal fade modal-preview" id="modalPreview" tabindex="-1" aria-labelledby="modalPreviewLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content modal-solid">
      <div class="modal-header">
        <h5 class="modal-title" id="modalPreviewLabel">Preview</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div id="previewSpinner" class="spinner-preview">
          <div class="spinner-border text-light" role="status"><span class="sr-only">Loading...</span></div>
        </div>
        <iframe id="previewIframe" src="" title="Preview"></iframe>
        <img id="previewImg" alt="Preview">
        <div id="previewFallback">
          <p class="mb-2">Tipe file ini tidak bisa di-preview. Silakan unduh untuk melihat isi.</p>
          <a id="previewDownloadBtn" href="#" class="btn btn-primary btn-sm">
            <i class="fas fa-download mr-1"></i> Download
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- MODAL: UPLOAD ke ARSIP --}}
<div class="modal fade" id="modalUploadArsip" tabindex="-1" aria-labelledby="modalUploadArsipLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form class="modal-content modal-solid" action="{{ route('laporan.upload') }}" method="POST" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="bucket" value="arsip">
      <div class="modal-header">
        <h5 class="modal-title" id="modalUploadArsipLabel">Upload Laporan ke Arsip</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Judul</label>
          <input type="text" name="judul" class="form-control" required>
        </div>

        <div class="form-row">
          <div class="form-group col-md-6">
            <label>Kategori Rapat</label>
            <select name="id_kategori" class="custom-select">
              <option value="">— Pilih Kategori —</option>
              @foreach($kategori as $k)
                <option value="{{ $k->id }}">{{ $k->nama }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group col-md-6">
            <label>Hubungkan dengan Rapat (opsional)</label>
            <select name="id_rapat" class="custom-select">
              <option value="">— Tidak dikaitkan —</option>
              @foreach($rapatList as $r)
                <option value="{{ $r->id }}">{{ $r->judul }} ({{ \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') }})</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group col-md-6">
            <label>Tanggal Laporan</label>
            <input type="date" name="tanggal_laporan" class="form-control" value="{{ now()->format('Y-m-d') }}">
          </div>
        </div>

        <div class="form-group">
          <label>Keterangan (opsional)</label>
          <textarea name="keterangan" class="form-control" rows="3"></textarea>
        </div>

        <div class="form-group">
          <label>File Laporan (maks 15MB)</label>
          <input type="file" name="file_laporan" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" type="submit">Upload</button>
      </div>
    </form>
  </div>
</div>

{{-- MODAL: EDIT --}}
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form id="formEdit" class="modal-content modal-solid" method="POST" enctype="multipart/form-data">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditLabel">Edit Laporan</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Judul</label>
          <input type="text" name="judul" id="e_judul" class="form-control" required>
        </div>

        <div class="form-row">
          <div class="form-group col-md-6">
            <label>Kategori Rapat</label>
            <select name="id_kategori" id="e_id_kategori" class="custom-select">
              <option value="">— Pilih Kategori —</option>
              @foreach($kategori as $k)
                <option value="{{ $k->id }}">{{ $k->nama }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group col-md-6">
            <label>Hubungkan dengan Rapat (opsional)</label>
            <select name="id_rapat" id="e_id_rapat" class="custom-select">
              <option value="">— Tidak dikaitkan —</option>
              @foreach($rapatList as $r)
                <option value="{{ $r->id }}">{{ $r->judul }} ({{ \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') }})</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group col-md-6">
            <label>Tanggal Laporan</label>
            <input type="date" name="tanggal_laporan" id="e_tanggal" class="form-control">
          </div>
        </div>

        <div class="form-group">
          <label>Keterangan (opsional)</label>
          <textarea name="keterangan" id="e_keterangan" class="form-control" rows="3"></textarea>
        </div>

        <div class="form-group">
          <label>Ganti File (opsional)</label>
          <input type="file" name="file_laporan" class="form-control">
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
  // inject data ke modal edit
  document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', () => {
      const id   = btn.dataset.id;
      document.getElementById('e_judul').value        = btn.dataset.judul || '';
      document.getElementById('e_id_kategori').value  = btn.dataset.id_kategori || '';
      document.getElementById('e_id_rapat').value     = btn.dataset.id_rapat || '';
      document.getElementById('e_tanggal').value      = btn.dataset.tanggal || '';
      document.getElementById('e_keterangan').value   = btn.dataset.keterangan || '';

      const form = document.getElementById('formEdit');
      form.action = "{{ url('/laporan/file') }}/" + id + "/update";
    });
  });

  // ===== PREVIEW: buka modal saat klik nama file
  (function(){
    const $modal = $('#modalPreview');
    const $title = $('#modalPreviewLabel');
    const $spinner = $('#previewSpinner');
    const $iframe = $('#previewIframe');
    const $img = $('#previewImg');
    const $fallback = $('#previewFallback');
    const $dlBtn = $('#previewDownloadBtn');

    function resetPreview(){
      $spinner.show();
      $iframe.hide().attr('src','');
      $img.hide().attr('src','');
      $fallback.hide();
      $dlBtn.attr('href','#');
    }

    $(document).on('click', '.preview-link', function(e){
      e.preventDefault();
      const url = this.dataset.url;
      const mime = (this.dataset.mime || '').toLowerCase();
      const fname = this.dataset.filename || 'Preview';

      resetPreview();
      $title.text('Preview — ' + fname);
      $modal.modal('show');

      if (mime.startsWith('application/pdf')) {
        $iframe.on('load', () => { $spinner.hide(); $iframe.show(); });
        $iframe.attr('src', url);
      } else if (mime.startsWith('image/')) {
        const img = new Image();
        img.onload = function(){ $spinner.hide(); $img.attr('src', url).show(); };
        img.onerror = function(){ showFallback(url); };
        img.src = url;
      } else {
        showFallback(url);
      }
    });

    function showFallback(downloadUrl){
      $spinner.hide();
      $fallback.show();
      $dlBtn.attr('href', downloadUrl.replace('/preview','/download'));
    }
  })();
</script>
@endpush
