@extends('layouts.app')
@section('title','Arsip Laporan')

@section('style')
<style>
  .table thead th{ text-align:center; vertical-align:middle; }
  .table td{ vertical-align: middle; }
  .table.no-hover tbody tr:hover { background: transparent !important; }

  .pill-file{
    display:inline-block; padding:.25rem .5rem; border-radius:8px;
    background: rgba(255,255,255,.06); border:1px solid rgba(226,232,240,.15);
    font-size:.8rem;
  }

  .btn-icon{
    width:34px; height:34px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center;
    border:1px solid rgba(255,255,255,.14); color:#fff; transition:.15s;
  }
  .btn-indigo{ background: linear-gradient(180deg,#6366f1,#4f46e5); }
  .btn-amber { background: linear-gradient(180deg,#f59e0b,#d97706); }
  .btn-rose  { background: linear-gradient(180deg,#ef4444,#dc2626); }
  .btn-cyan  { background: linear-gradient(180deg,#06b6d4,#0891b2); }
  .btn-emerald{ background: linear-gradient(180deg,#22c55e,#16a34a); }
  .btn-icon:hover{ filter: brightness(1.06); }

  .filter-tight .form-group{ margin-bottom:.5rem; }
  .filter-tight label{ margin-bottom:.25rem; font-weight:600; color:#dbe7ff; font-size:.85rem; }
</style>
@endsection

@section('content')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Arsip Laporan</h3>

    {{-- Tombol Upload ke Arsip (modal) --}}
    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalUpload">
      <i class="fas fa-upload mr-1"></i> Upload ke Arsip
    </button>
  </div>

  {{-- ALERT --}}
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

        {{-- Opsional: filter Rapat (controller sekarang belum menyaring ini; tetap ditampilkan agar seragam UI) --}}
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

  {{-- TABEL --}}
  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm mb-0 no-hover">
          <thead>
            <tr class="text-center">
              <th style="width:54px;">#</th>
              <th>Judul</th>
              <th style="width:180px;">Kategori</th>
              <th style="width:210px;">Rapat</th>
              <th style="width:140px;">Tgl. Laporan</th>
              <th>Nama File</th>
              <th style="width:180px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
          @forelse ($uploads as $i => $u)
            <tr>
              <td class="text-center">{{ ($uploads->currentPage()-1) * $uploads->perPage() + $i + 1 }}</td>

              <td>
                <strong>{{ $u->judul }}</strong>
                @if($u->keterangan)
                  <div class="text-muted" style="font-size:12px">{{ $u->keterangan }}</div>
                @endif
              </td>

              <td>{{ $u->nama_kategori ?? '-' }}</td>

              <td>
                @if(!empty($u->judul_rapat))
                  {{ \Illuminate\Support\Str::limit($u->judul_rapat,38) }}
                  @if(!empty($u->tgl_rapat))
                    <div class="text-muted" style="font-size:12px">
                      {{ \Carbon\Carbon::parse($u->tgl_rapat)->format('d/m/Y') }}
                    </div>
                  @endif
                @else
                  -
                @endif
              </td>

              <td>
                @php $tgl = $u->tanggal_laporan ?: $u->created_at; @endphp
                {{ \Carbon\Carbon::parse($tgl)->format('d/m/Y') }}
              </td>

              <td><span class="pill-file">{{ $u->file_name }}</span></td>

              <td class="text-center">
                <div class="d-inline-flex">
                  {{-- Kembalikan ke Laporan (Unarchive) --}}
                  <form action="{{ route('laporan.file.unarchive',$u->id) }}" method="POST" class="d-inline mr-1"
                        onsubmit="return confirm('Kembalikan file ini ke halaman Laporan?')">
                    @csrf
                    <button class="btn-icon btn-emerald" title="Kembalikan ke Laporan">
                      <i class="fas fa-undo-alt"></i>
                    </button>
                  </form>

                  {{-- Download --}}
                  <a href="{{ route('laporan.file.download',$u->id) }}"
                     class="btn-icon btn-cyan mr-1" data-toggle="tooltip" title="Download">
                    <i class="fas fa-download"></i>
                  </a>

                  {{-- Edit (modal) --}}
                  <button type="button"
                          class="btn-icon btn-amber mr-1 btn-edit"
                          data-id="{{ $u->id }}"
                          data-judul="{{ e($u->judul) }}"
                          data-id_kategori="{{ $u->id_kategori }}"
                          data-id_rapat="{{ $u->id_rapat }}"
                          data-tanggal="{{ $u->tanggal_laporan ? \Carbon\Carbon::parse($u->tanggal_laporan)->format('Y-m-d') : '' }}"
                          data-keterangan="{{ e($u->keterangan ?? '') }}"
                          data-toggle="modal" data-target="#modalEdit" title="Edit">
                    <i class="fas fa-edit"></i>
                  </button>

                  {{-- Hapus --}}
                  <form action="{{ route('laporan.file.destroy',$u->id) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('Hapus lampiran ini dari arsip?')">
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

  {{-- Pagination di luar card (pakai param file_page dari controller) --}}
  <div class="mt-3">
    {{ $uploads->appends(request()->query())->links() }}
  </div>
</div>

{{-- MODAL: UPLOAD (langsung ke arsip) --}}
<div class="modal fade" id="modalUpload" tabindex="-1" aria-labelledby="modalUploadLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form class="modal-content modal-solid" action="{{ route('laporan.upload') }}" method="POST" enctype="multipart/form-data">
      @csrf
      {{-- tidak perlu hidden bucket; pengarsipan permanen ditentukan oleh tanggal/halaman ini --}}
      <div class="modal-header">
        <h5 class="modal-title" id="modalUploadLabel">Upload Laporan ke Arsip</h5>
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
  // Inject data ke modal edit
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
</script>
@endpush
