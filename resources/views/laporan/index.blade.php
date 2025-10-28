@extends('layouts.app')
@section('title','Laporan')

@section('style')
<style>
  /* ===== Table & UI (desktop) ===== */
  .table thead th{ text-align:center; vertical-align:middle; }
  .table td{ vertical-align: middle; }
  .table.no-hover tbody tr:hover { background: transparent !important; }

  .pill-file{
    display:inline-block; padding:.25rem .5rem; border-radius:8px;
    background: rgba(255,255,255,.06); border:1px solid rgba(226,232,240,.15);
    font-size:.8rem; max-width: 280px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
  }

  .btn-icon{
    width:34px; height:34px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center;
    border:1px solid rgba(255,255,255,.14); color:#fff; transition:.15s;
  }
  .btn-indigo{ background: linear-gradient(180deg,#6366f1,#4f46e5); }
  .btn-amber { background: linear-gradient(180deg,#f59e0b,#d97706); }
  .btn-rose  { background: linear-gradient(180deg,#ef4444,#dc2626); }
  .btn-cyan  { background: linear-gradient(180deg,#06b6d4,#0891b2); }
  .btn-lime  { background: linear-gradient(180deg,#84cc16,#65a30d); }
  .btn-icon:hover{ filter: brightness(1.06); }

  .filter-tight .form-group{ margin-bottom:.5rem; }
  .filter-tight label{ margin-bottom:.25rem; font-weight:600; color:#dbe7ff; font-size:.85rem; }

  .badge-soft{
    border-radius:999px; padding:.2rem .5rem; font-weight:700; letter-spacing:.2px;
    background: rgba(255,255,255,.06); border:1px solid rgba(226,232,240,.15);
  }
  .badge-green { background: linear-gradient(180deg, rgba(34,197,94,.22), rgba(34,197,94,.12)); }
  .badge-slate { background: linear-gradient(180deg, rgba(148,163,184,.22), rgba(148,163,184,.12)); }

  .hint{ display:inline-block; font-size:.72rem; color:#9fb0cd; margin-top:.25rem; }

  /* ====== Mobile cards ====== */
  @media (max-width: 575.98px){
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

    /* action buttons full-width */
    td[data-label="Aksi"] .d-inline-flex{ width:100%; gap:6px; }
    td[data-label="Aksi"] .btn-icon{ flex:1; height:38px; }
    .pill-file{ max-width:100%; }
  }
</style>
@endsection

@section('content')
<div class="container">

  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Laporan</h3>
    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalUpload">
      <i class="fas fa-upload mr-1"></i> Upload
    </button>
  </div>

  {{-- Alerts --}}
  @if(session('ok'))      <div class="alert alert-success">{{ session('ok') }}</div> @endif
  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div>   @endif

  {{-- Filter --}}
  <form method="GET" action="{{ route('laporan.index') }}" class="card mb-3 filter-tight">
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
          <select name="id_kategori" class="custom-select custom-select-sm">
            <option value="">Semua</option>
            @foreach($kategori as $k)
              <option value="{{ $k->id }}" {{ ($filter['id_kat']??'')==$k->id ? 'selected':'' }}>{{ $k->nama }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-3">
          <label>Status Notulensi</label>
          <select name="status_notulensi" class="custom-select custom-select-sm">
            <option value="">Semua</option>
            <option value="sudah" {{ ($filter['status_n']??'')=='sudah' ? 'selected':'' }}>Sudah Ada</option>
            <option value="belum" {{ ($filter['status_n']??'')=='belum' ? 'selected':'' }}>Belum Ada</option>
          </select>
        </div>
        <div class="form-group col-md-2">
          <label>&nbsp;</label>
          <div class="d-flex">
            <button class="btn btn-primary btn-sm mr-2"><i class="fas fa-filter mr-1"></i> Terapkan</button>
            <a href="{{ route('laporan.index') }}" class="btn btn-outline-light btn-sm">Reset</a>
          </div>
        </div>
      </div>
    </div>
  </form>

  {{-- =================== REKAP RAPAT =================== --}}
<div class="card mb-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <b>Rekap Rapat</b>
    <a href="{{ route('laporan.cetak', request()->query()) }}" class="btn btn-outline-light btn-sm" target="_blank">
      <i class="fas fa-print mr-1"></i> Cetak Rekap
    </a>
  </div>

  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm mb-0 no-hover">
        <thead>
          <tr class="text-center">
            <th style="width:50px;">#</th>
            <th>Judul</th>
            <th style="width:260px;">Detail Rapat</th>
            <th style="width:90px;">Hadir</th>
            <th style="width:110px;">Notulensi</th>
            <th style="width:110px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rekap as $i => $r)
            @php $rowNo = ($rekap->currentPage()-1) * $rekap->perPage() + $i + 1; @endphp
            <tr>
              <td class="text-center">{{ $rowNo }}</td>

              <td class="align-middle">
                <strong>{{ $r->judul }}</strong>
              </td>

              {{-- ===== Kolom Gabungan: Kategori + Tanggal + Waktu + Tempat ===== --}}
              <td class="text-center align-middle">
                <div class="mb-1">
                  <span class="badge-soft badge-slate px-2 py-1">
                    {{ $r->nama_kategori ?? '-' }}
                  </span>
                </div>
                <div style="font-size:12px;">
                  {{ \Carbon\Carbon::parse($r->tanggal)->translatedFormat('d M Y') }}
                </div>
                <div style="font-size:12px;">
                  {{ $r->waktu_mulai }} WIT
                </div>
                <div class="text-muted" style="font-size:11px;">
                  <i class="fas fa-map-marker-alt mr-1"></i>{{ $r->tempat }}
                </div>
              </td>

              <td class="text-center align-middle">
                <span class="badge-soft badge-green">{{ $r->jml_hadir ?? 0 }}</span>
              </td>

              <td class="text-center align-middle">
                @if(($r->ada_notulensi ?? 0)==1)
                  <span class="badge badge-success">Sudah</span>
                @else
                  <span class="badge badge-secondary">Belum</span>
                @endif
              </td>

              <td class="text-center align-middle">
                <div class="d-inline-flex justify-content-center">
                  <a href="{{ route('laporan.gabungan', $r->id) }}"
                     class="btn-icon btn-cyan mr-1"
                     target="_blank" title="Download Gabungan PDF">
                    <i class="fas fa-download"></i>
                  </a>
                  <form action="{{ route('laporan.rapat.archive', $r->id) }}"
                        method="POST" class="d-inline"
                        onsubmit="return confirm('Arsipkan rapat ini? Sistem akan membuat file gabungan PDF dan memindahkannya ke Arsip.')">
                    @csrf
                    <button class="btn-icon btn-lime" title="Arsipkan Rapat">
                      <i class="fas fa-archive"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted p-4">Tidak ada data.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>


  <div class="mt-2">
    {{ $rekap->appends(request()->query())->links() }}
  </div>

  {{-- =================== UNGGAHAN FILE (AKTIF) =================== --}}
  <div class="card mt-4">
    <div class="card-header"><b>Unggahan Laporan</b></div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm mb-0 no-hover">
          <thead>
            <tr class="text-center">
              <th style="width:54px;">#</th>
              <th>Judul</th>
              <th style="width:180px;">Kategori</th>
              <th style="width:140px;">Tgl. Laporan</th>
              <th>Nama File</th>
              <th style="width:200px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse($uploads as $i => $u)
              @php $rowNo = ($uploads->currentPage()-1) * $uploads->perPage() + $i + 1; @endphp
              <tr>
                <td class="text-center" data-label="#">{{ $rowNo }}</td>

                <td data-label="Judul">
                  <strong>{{ $u->judul }}</strong>
                  @if($u->keterangan)
                    <div class="text-muted" style="font-size:12px">{{ $u->keterangan }}</div>
                  @endif
                  @if($u->judul_rapat)
                    <div class="text-muted" style="font-size:12px">
                      Terkait: {{ \Illuminate\Support\Str::limit($u->judul_rapat,42) }}
                    </div>
                  @endif
                </td>

                <td data-label="Kategori">{{ $u->nama_kategori ?? '-' }}</td>

                <td data-label="Tgl. Laporan">
                  @php $tgl = $u->tanggal_laporan ?: $u->created_at; @endphp
                  {{ \Carbon\Carbon::parse($tgl)->format('d/m/Y') }}
                </td>

                <td data-label="Nama File">
                  <span class="pill-file">{{ $u->file_name }}</span>
                </td>

                <td class="text-center" data-label="Aksi">
                  <div class="d-inline-flex">
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

                    <form action="{{ route('laporan.file.archive',$u->id) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Pindahkan ke Arsip? Item ini akan hilang dari halaman Laporan dan muncul di Arsip.')">
                      @csrf
                      <button class="btn-icon btn-lime mr-1" title="Arsipkan">
                        <i class="fas fa-archive"></i>
                      </button>
                    </form>

                    <form action="{{ route('laporan.file.destroy',$u->id) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Hapus lampiran ini?')">
                      @csrf @method('DELETE')
                      <button class="btn-icon btn-rose" title="Hapus">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="6" class="text-center text-muted p-4">Belum ada unggahan.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="mt-3">
    {{ $uploads->appends(request()->query())->links() }}
  </div>
</div>

{{-- =================== MODAL UPLOAD =================== --}}
<div class="modal fade" id="modalUpload" tabindex="-1" aria-labelledby="modalUploadLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form class="modal-content modal-solid" action="{{ route('laporan.upload') }}" method="POST" enctype="multipart/form-data">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title" id="modalUploadLabel">Upload Laporan</h5>
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

{{-- =================== MODAL EDIT =================== --}}
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
            <input type="number" name="id_rapat" id="e_id_rapat" class="form-control" placeholder="ID rapat (opsional)">
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
  // Inject data ke modal edit
  document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', () => {
      const id  = btn.dataset.id;
      document.getElementById('e_judul').value        = btn.dataset.judul || '';
      document.getElementById('e_id_kategori').value  = btn.dataset.id_kategori || '';
      document.getElementById('e_id_rapat').value     = btn.dataset.id_rapat || '';
      document.getElementById('e_tanggal').value      = btn.dataset.tanggal || '';
      document.getElementById('e_keterangan').value   = btn.dataset.keterangan || '';

      const form = document.getElementById('formEdit');
      form.action = "{{ url('/laporan/file') }}/" + id + "/update";
    });
  });
</script>
@endpush
