@extends('layouts.app')
@section('title','Arsip Laporan')

@section('content')
<div class="container">
    <h4 class="mb-3">Lampiran Laporan yang Diunggah</h4>

    {{-- FILTER --}}
    <form class="card mb-3" method="GET" action="{{ route('laporan.arsip') }}">
        <div class="card-body">
            <div class="form-row">
                <div class="col-md-3">
                    <label>Dari Tanggal</label>
                    <input type="date" name="dari" value="{{ $filter['dari'] ?? '' }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label>Sampai Tanggal</label>
                    <input type="date" name="sampai" value="{{ $filter['sampai'] ?? '' }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label>Kategori Rapat</label>
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
                    <label>Cari Judul/Keterangan</label>
                    <input type="text" name="q" value="{{ $filter['qsearch'] ?? '' }}" class="form-control" placeholder="Ketik kata kunci ...">
                </div>
            </div>
            <div class="mt-3 d-flex align-items-center">
                <button class="btn btn-primary mr-2">Terapkan Filter</button>

                {{-- Tombol Upload (buka modal) --}}
                <button type="button" class="btn btn-outline-secondary ml-auto" data-toggle="modal" data-target="#modalUpload">
                    <i class="fas fa-upload"></i> Upload
                </button>
            </div>
        </div>
    </form>

    {{-- TABEL LAMPIRAN --}}
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped table-bordered mb-0">
                <thead>
                    <tr class="text-center">
                        <th style="width:40px;">#</th>
                        <th>Judul</th>
                        <th style="width:220px;">Kategori</th>
                        <th style="width:120px;">Rapat</th>
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
                            <td>{{ $u->judul_rapat ? Str::limit($u->judul_rapat, 18) : '-' }}</td>
                            <td>
                                @php
                                    $tgl = $u->tanggal_laporan ?: $u->created_at;
                                @endphp
                                {{ \Carbon\Carbon::parse($tgl)->format('d/m/Y') }}
                            </td>
                            <td>{{ $u->file_name }}</td>
                            <td class="text-center">
                                <a href="{{ route('laporan.file.download',$u->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-download"></i> Download
                                </a>
                                <button type="button"
                                        class="btn btn-sm btn-warning btn-edit"
                                        data-id="{{ $u->id }}"
                                        data-judul="{{ e($u->judul) }}"
                                        data-id_kategori="{{ $u->id_kategori }}"
                                        data-id_rapat="{{ $u->id_rapat }}"
                                        data-tanggal="{{ $u->tanggal_laporan ? \Carbon\Carbon::parse($u->tanggal_laporan)->format('Y-m-d') : '' }}"
                                        data-keterangan="{{ e($u->keterangan ?? '') }}"
                                        data-toggle="modal" data-target="#modalEdit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('laporan.file.destroy',$u->id) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Hapus lampiran ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-danger">
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

{{-- MODAL: UPLOAD --}}
<div class="modal fade" id="modalUpload" tabindex="-1" aria-labelledby="modalUploadLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form class="modal-content" action="{{ route('laporan.upload') }}" method="POST" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="bucket" value="arsip"> {{-- penting: supaya diarahkan ke Arsip --}}
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
                <select name="id_kategori" class="form-control">
                    <option value="">— Pilih Kategori —</option>
                    @foreach($kategori as $k)
                      <option value="{{ $k->id }}">{{ $k->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label>Hubungkan dengan Rapat (opsional)</label>
                <select name="id_rapat" class="form-control">
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

{{-- MODAL: EDIT --}}
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form id="formEdit" class="modal-content" method="POST" enctype="multipart/form-data">
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
                <select name="id_kategori" id="e_id_kategori" class="form-control">
                    <option value="">— Pilih Kategori —</option>
                    @foreach($kategori as $k)
                      <option value="{{ $k->id }}">{{ $k->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label>Hubungkan dengan Rapat (opsional)</label>
                <select name="id_rapat" id="e_id_rapat" class="form-control">
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

@section('script')
<script>
  // inject data ke modal edit
  document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', () => {
      const id   = btn.dataset.id;
      const jud  = btn.dataset.judul || '';
      const idk  = btn.dataset.id_kategori || '';
      const idr  = btn.dataset.id_rapat || '';
      const tgl  = btn.dataset.tanggal || '';
      const ket  = btn.dataset.keterangan || '';

      document.getElementById('e_judul').value = jud;
      document.getElementById('e_id_kategori').value = idk;
      document.getElementById('e_id_rapat').value = idr;
      document.getElementById('e_tanggal').value = tgl;
      document.getElementById('e_keterangan').value = ket;

      const form = document.getElementById('formEdit');
      form.action = "{{ url('/laporan/file') }}/" + id + "/update";
    });
  });
</script>
@endsection
