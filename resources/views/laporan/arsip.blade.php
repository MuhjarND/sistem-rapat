@extends('layouts.app')
@section('title','Arsip Laporan')

@push('style')
<style>
  /* Filter card compact */
  .filter-card .card-body{ padding: 14px 16px; }
  .filter-card .form-group{ margin-bottom: .6rem; }
  .filter-hint{ font-size:.78rem; color: var(--muted); }

  /* Table look */
  .table thead th{
    text-align:center; vertical-align:middle;
    text-transform:uppercase; letter-spacing:.35px; font-size:.74rem;
    background: rgba(79,70,229,.12);
  }
  .table td{ vertical-align:middle; font-size:.9rem; }
  .table tbody tr:hover{ background: rgba(14,165,233,.06); }

  /* Badge filename wrap */
  .filename{ word-break: break-all; }

  /* Make modal body breathing a bit */
  .modal-solid .modal-body{ padding-top: 14px; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center mb-3">
        <h4 class="mb-0" style="font-weight:800;color:#fff;">Lampiran Laporan yang Diunggah</h4>
        <span class="ml-2 filter-hint">Kelola & cari arsip file laporan Anda</span>
    </div>

    {{-- ============ FILTER ============ --}}
    <form class="card filter-card mb-3" method="GET" action="{{ route('laporan.arsip') }}">
        <div class="card-body">
            <div class="form-row align-items-end">
                <div class="col-md-3">
                    <label class="mb-1">Dari Tanggal</label>
                    <input type="date" name="dari" value="{{ $filter['dari'] ?? '' }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="mb-1">Sampai Tanggal</label>
                    <input type="date" name="sampai" value="{{ $filter['sampai'] ?? '' }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="mb-1">Kategori Rapat</label>
                    <select name="id_kategori" class="form-control">
                        <option value="">Semua</option>
                        @foreach($kategori as $k)
                            <option value="{{ $k->id }}" {{ ($filter['id_kat']??'')==$k->id ? 'selected':'' }}>
                                {{ $k->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="mb-1">Cari Judul/Keterangan</label>
                    <div class="input-group">
                        <input type="text" name="q" value="{{ $filter['qsearch'] ?? '' }}" class="form-control" placeholder="Ketik kata kunci ...">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-filter mr-1"></i> Terapkan
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tombol Upload (buka modal) --}}
            <div class="mt-2 d-flex">
                <button type="button" class="btn btn-outline-light ml-auto" data-toggle="modal" data-target="#modalUpload">
                    <i class="fas fa-upload mr-1"></i> Upload
                </button>
            </div>
        </div>
    </form>

    {{-- ============ TABEL LAMPIRAN ============ --}}
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped table-hover table-sm mb-0">
                <thead>
                    <tr>
                        <th style="width:46px;">#</th>
                        <th>Judul</th>
                        <th style="width:220px;">Kategori</th>
                        <th style="width:220px;">Rapat</th>
                        <th style="width:130px;">Tgl. Laporan</th>
                        <th>Nama File</th>
                        <th style="width:160px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($uploads as $i => $u)
                        <tr>
                            <td class="text-center">{{ $i+1 }}</td>
                            <td>
                                <strong>{{ $u->judul }}</strong>
                                @if($u->keterangan)
                                    <div class="text-muted" style="font-size:12px">{{ $u->keterangan }}</div>
                                @endif
                            </td>
                            <td>{{ $u->nama_kategori ?? '-' }}</td>
                            <td>
                                @if($u->judul_rapat)
                                  {{ \Illuminate\Support\Str::limit($u->judul_rapat, 38) }}
                                @else
                                  <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @php $tgl = $u->tanggal_laporan ?: $u->created_at; @endphp
                                {{ \Carbon\Carbon::parse($tgl)->format('d/m/Y') }}
                            </td>
                            <td class="filename">{{ $u->file_name }}</td>
                            <td class="text-center">
                                <a href="{{ route('laporan.file.download',$u->id) }}" class="btn btn-sm btn-primary" data-toggle="tooltip" title="Download">
                                    <i class="fas fa-download"></i>
                                </a>
                                <button type="button"
                                        class="btn btn-sm btn-warning btn-edit"
                                        data-id="{{ $u->id }}"
                                        data-judul="{{ e($u->judul) }}"
                                        data-id_kategori="{{ $u->id_kategori }}"
                                        data-id_rapat="{{ $u->id_rapat }}"
                                        data-tanggal="{{ $u->tanggal_laporan ? \Carbon\Carbon::parse($u->tanggal_laporan)->format('Y-m-d') : '' }}"
                                        data-keterangan="{{ e($u->keterangan ?? '') }}"
                                        data-toggle="modal" data-target="#modalEdit"
                                        title="Edit" data-placement="top">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('laporan.file.destroy',$u->id) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Hapus lampiran ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-danger" data-toggle="tooltip" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted p-4">Belum ada unggahan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ============ MODAL: UPLOAD ============ --}}
<div class="modal fade" id="modalUpload" tabindex="-1" aria-labelledby="modalUploadLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form class="modal-content modal-solid" action="{{ route('laporan.upload') }}" method="POST" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="bucket" value="arsip"> {{-- penting: diarahkan ke Arsip --}}
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
                <select name="id_kategori" class="form-control select2-basic" data-parent="#modalUpload">
                    <option value="">— Pilih Kategori —</option>
                    @foreach($kategori as $k)
                      <option value="{{ $k->id }}">{{ $k->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label>Hubungkan dengan Rapat (opsional)</label>
                <select name="id_rapat" class="form-control select2-basic" data-parent="#modalUpload">
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
        <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
        <button class="btn btn-primary" type="submit">Upload</button>
      </div>
    </form>
  </div>
</div>

{{-- ============ MODAL: EDIT ============ --}}
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
                <select name="id_kategori" id="e_id_kategori" class="form-control select2-basic" data-parent="#modalEdit">
                    <option value="">— Pilih Kategori —</option>
                    @foreach($kategori as $k)
                      <option value="{{ $k->id }}">{{ $k->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label>Hubungkan dengan Rapat (opsional)</label>
                <select name="id_rapat" id="e_id_rapat" class="form-control select2-basic" data-parent="#modalEdit">
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
        <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
        <button class="btn btn-primary" type="submit">Simpan</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // Init Select2 untuk select single di dalam modal (gunakan class .select2-basic)
  $(function(){
    function initSelect2In($modal){
      $modal.find('.select2-basic').each(function(){
        var parentSel = $(this).data('parent') || null;
        $(this).select2({
          width: '100%',
          dropdownParent: parentSel ? $(parentSel) : $modal,
          placeholder: $(this).find('option:first').text()
        });
      });
    }

    $('#modalUpload').on('shown.bs.modal', function(){ initSelect2In($(this)); });
    $('#modalEdit').on('shown.bs.modal', function(){ initSelect2In($(this)); });

    // inject data ke modal edit
    $('.btn-edit').on('click', function(){
      const btn = this.dataset;
      $('#e_judul').val(btn.judul || '');
      $('#e_id_kategori').val(btn.id_kategori || '').trigger('change');
      $('#e_id_rapat').val(btn.id_rapat || '').trigger('change');
      $('#e_tanggal').val(btn.tanggal || '');
      $('#e_keterangan').val(btn.keterangan || '');
      $('#formEdit').attr('action', "{{ url('/laporan/file') }}/" + btn.id + "/update");
    });

    $('[data-toggle="tooltip"]').tooltip();
  });
</script>
@endpush
