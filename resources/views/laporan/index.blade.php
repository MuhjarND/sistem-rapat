@extends('layouts.app')
@section('content')
<div class="container">
  <h3>Laporan Rapat (Undangan • Absensi • Notulensi)</h3>

  <form class="card mb-3" method="GET" action="{{ route('laporan.index') }}">
    <div class="card-body">
      <div class="row">
        <div class="col-md-3">
          <label>Dari Tanggal</label>
          <input type="date" name="dari" class="form-control" value="{{ $filter['dari'] ?? '' }}">
        </div>
        <div class="col-md-3">
          <label>Sampai Tanggal</label>
          <input type="date" name="sampai" class="form-control" value="{{ $filter['sampai'] ?? '' }}">
        </div>
        <div class="col-md-3">
          <label>Kategori Rapat</label>
          <select name="id_kategori" class="form-control">
            <option value="">Semua</option>
            @foreach($kategori as $k)
              <option value="{{ $k->id }}" {{ ($filter['id_kat']??'')==$k->id ? 'selected':'' }}>{{ $k->nama }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label>Status Notulensi</label>
          <select name="status_notulensi" class="form-control">
            <option value="">Semua</option>
            <option value="sudah" {{ ($filter['status_n']??'')=='sudah'?'selected':'' }}>Sudah Ada</option>
            <option value="belum" {{ ($filter['status_n']??'')=='belum'?'selected':'' }}>Belum Ada</option>
          </select>
        </div>
      </div>
      <div class="mt-3 d-flex align-items-center">
        <button class="btn btn-primary mr-2">Terapkan Filter</button>
        <a href="{{ route('laporan.cetak', request()->all()) }}" class="btn btn-success mr-2" target="_blank">Cetak PDF</a>

        <button type="button" class="btn btn-outline-secondary ml-auto" data-toggle="modal" data-target="#modalUpload">
          <i class="fas fa-upload"></i> Upload
        </button>
      </div>
    </div>
  </form>

  {{-- Rekap per-rapat (tanpa kolom diundang/hadir/tidak/izin) --}}
  <div class="card">
    <div class="card-body p-0">
      <table class="table table-striped table-bordered mb-0">
        <thead class="text-center">
          <tr>
            <th style="width:40px;">#</th>
            <th>Judul Rapat</th>
            <th>Kategori</th>
            <th>Tanggal</th>
            <th>Tempat</th>
            <th class="text-center">Notulensi</th>
            <th style="width:120px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($data as $i => $r)
            <tr>
              <td class="text-center">{{ $i+1 }}</td>
              <td>{{ $r->judul }}</td>
              <td>{{ $r->nama_kategori ?? '-' }}</td>
              <td>{{ \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') }} {{ $r->waktu_mulai }}</td>
              <td>{{ $r->tempat }}</td>
              <td class="text-center">
                <span class="badge {{ $r->ada_notulensi ? 'badge-success' : 'badge-secondary' }}">
                  {{ $r->ada_notulensi ? 'Ada' : 'Belum' }}
                </span>
              </td>
              <td class="text-center">
                <a href="{{ route('laporan.gabungan', $r->id) }}" class="btn btn-sm btn-success" target="_blank">UNDUH</a>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted p-4">Tidak ada data.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Lampiran yang diunggah --}}
  <div class="card mt-4">
    <div class="card-header">
      <strong>Lampiran Laporan yang Diunggah</strong>
    </div>
    <div class="card-body p-0">
      <table class="table table-bordered table-striped mb-0">
        <thead>
          <tr>
            <th style="width:60px;">#</th>
            <th>Judul</th>
            <th>Kategori</th>
            <th>Rapat</th>
            <th>Tgl. Laporan</th>
            <th>Nama File</th>
            <th style="width:220px;" class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($uploads as $i => $u)
            <tr>
              <td class="text-center">{{ $i+1 }}</td>
              <td>
                <div class="font-weight-bold">{{ $u->judul }}</div>
                @if($u->keterangan)
                  <small class="text-muted">{{ $u->keterangan }}</small>
                @endif
              </td>
              <td>{{ $u->nama_kategori ?? '-' }}</td>
              <td>{{ $u->judul_rapat ?? '-' }}</td>
              <td>{{ $u->tanggal_laporan ? \Carbon\Carbon::parse($u->tanggal_laporan)->format('d/m/Y') : '-' }}</td>
              <td>{{ $u->file_name }}</td>
              <td class="text-center">
                <a href="{{ route('laporan.file.download', $u->id) }}" class="btn btn-sm btn-primary">
                  <i class="fas fa-download"></i> Download
                </a>
                {{-- tombol edit pakai 1 modal global --}}
                <button type="button"
                        class="btn btn-sm btn-warning btn-edit-upload"
                        data-toggle="modal" data-target="#modalEditUpload"
                        data-url="{{ route('laporan.updateFile', $u->id) }}"
                        data-judul="{{ e($u->judul) }}"
                        data-id_kategori="{{ $u->id_kategori }}"
                        data-tanggal="{{ $u->tanggal_laporan }}"
                        data-keterangan="{{ e($u->keterangan) }}">
                  <i class="fas fa-edit"></i>
                </button>
                <form action="{{ route('laporan.file.destroy', $u->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus file ini?')">
                  @csrf @method('DELETE')
                  <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted p-4">Belum ada file laporan.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>

{{-- MODAL UPLOAD BARU --}}
<div class="modal fade" id="modalUpload" tabindex="-1" role="dialog" aria-labelledby="modalUploadLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <form class="modal-content" action="{{ route('laporan.upload') }}" method="POST" enctype="multipart/form-data">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title" id="modalUploadLabel"><i class="fas fa-upload"></i> Upload Laporan</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <div class="form-row">
          <div class="form-group col-md-8">
            <label>Kategori Rapat</label>
            <select name="id_kategori" id="id_kategori" class="form-control">
              <option value="">— Pilih Kategori (opsional) —</option>
              @foreach($kategori as $k)
                <option value="{{ $k->id }}">{{ $k->nama }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group col-md-4">
            <label>File <span class="text-danger">*</span></label>
            <input type="file" name="file_laporan" class="form-control" required>
            <small class="text-muted d-block">Max 15MB. (pdf/doc/docx/xls/xlsx/jpg/png)</small>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group col-md-8">
            <label>Judul Laporan <span class="text-danger">*</span></label>
            <input type="text" name="judul" class="form-control" required>
          </div>
          <div class="form-group col-md-4">
            <label>Tanggal Laporan</label>
            <input type="date" name="tanggal_laporan" class="form-control">
          </div>
        </div>

        <div class="form-group">
          <label>Keterangan</label>
          <textarea name="keterangan" rows="3" class="form-control" placeholder="Catatan singkat isi laporan (opsional)"></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Upload</button>
      </div>
    </form>
  </div>
</div>

{{-- MODAL EDIT GLOBAL (di luar tabel) --}}
<div class="modal fade" id="modalEditUpload" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formEditUpload" action="#" method="POST" enctype="multipart/form-data" class="modal-content">
      @csrf @method('PUT')
      <div class="modal-header">
        <h5 class="modal-title">Edit Laporan</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Judul</label>
          <input type="text" name="judul" id="edit_judul" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Kategori</label>
          <select name="id_kategori" id="edit_id_kategori" class="form-control">
            <option value="">— Tidak dikaitkan —</option>
            @foreach($kategori as $k)
              <option value="{{ $k->id }}">{{ $k->nama }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label>Tanggal Laporan</label>
          <input type="date" name="tanggal_laporan" id="edit_tanggal" class="form-control">
        </div>
        <div class="form-group">  
          <label>Keterangan</label>
          <textarea name="keterangan" id="edit_keterangan" class="form-control"></textarea>
        </div>
        <div class="form-group">
          <label>Ganti File (opsional)</label>
          <input type="file" name="file_laporan" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-success">Simpan</button>
      </div>
    </form>
  </div>
</div>
@endsection

@section('script')
<script>
  // Isi modal edit saat tombol Edit diklik
  $(document).on('click', '.btn-edit-upload', function () {
    var btn   = $(this);
    var url   = btn.data('url');
    var judul = btn.data('judul') || '';
    var idKat = btn.data('id_kategori') || '';
    var tgl   = btn.data('tanggal') || '';
    var ket   = btn.data('keterangan') || '';

    $('#formEditUpload').attr('action', url);
    $('#edit_judul').val(judul);
    $('#edit_id_kategori').val(idKat);
    $('#edit_tanggal').val(tgl);
    $('#edit_keterangan').val(ket);
  });
</script>
@endsection
