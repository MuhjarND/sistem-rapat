@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between mb-3">
        <h3>Daftar Rapat</h3>
        @if(Auth::user()->role == 'admin')
            <!-- Tombol Modal, bukan link -->
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalTambahRapat">
                + Tambah Rapat
            </button>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('rapat.index') }}" class="mb-3">
        <div class="row">
            <div class="col-md-4">
                <select name="kategori" class="form-control">
                    <option value="">-- Semua Kategori --</option>
                    @foreach($daftar_kategori as $kategori)
                        <option value="{{ $kategori->id }}" {{ request('kategori') == $kategori->id ? 'selected' : '' }}>
                            {{ $kategori->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-info">Filter</button>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped m-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nomor Undangan</th>
                        <th>Judul</th>
                        <th>Kategori</th>
                        <th>Tanggal</th>
                        <th>Waktu</th>
                        <th>Tempat</th>
                        <th>Pimpinan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($daftar_rapat as $no => $rapat)
                    <tr>
                        <td>{{ $no + 1 }}</td>
                        <td>{{ $rapat->nomor_undangan }}</td>
                        <td>{{ $rapat->judul }}</td>
                        <td>{{ $rapat->nama_kategori ?? '-' }}</td>
                        <td>{{ \Carbon\Carbon::parse($rapat->tanggal)->format('d M Y') }}</td>
                        <td>{{ $rapat->waktu_mulai }}</td>
                        <td>{{ $rapat->tempat }}</td>
                        <td>{{ $rapat->nama_pimpinan }}<br><small>{{ $rapat->jabatan_pimpinan }}</small></td>
                        <td>
                            <span class="badge
                                @if($rapat->status_label == 'Akan Datang') badge-info
                                @elseif($rapat->status_label == 'Berlangsung') badge-success
                                @elseif($rapat->status_label == 'Selesai') badge-secondary
                                @elseif($rapat->status_label == 'Dibatalkan') badge-danger
                                @endif">
                                {{ $rapat->status_label }}
                            </span>
                            @if($rapat->status !== 'dibatalkan' && $rapat->status_label == 'Akan Datang' && Auth::user()->role == 'admin')
                                <form action="{{ route('rapat.batal', $rapat->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button class="btn btn-danger btn-sm" onclick="return confirm('Batalkan rapat?')">Batalkan</button>
                                </form>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('rapat.show', $rapat->id) }}" class="btn btn-info btn-sm">Detail</a>
                            @if(Auth::user()->role == 'admin')
                            <a href="{{ route('rapat.edit', $rapat->id) }}" class="btn btn-warning btn-sm">Edit</a>
                            <form action="{{ route('rapat.destroy', $rapat->id) }}" method="POST" class="d-inline"
                                onsubmit="return confirm('Hapus rapat ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm">Hapus</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center">Belum ada data rapat.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Rapat -->
<div class="modal fade" id="modalTambahRapat" tabindex="-1" role="dialog" aria-labelledby="tambahRapatLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tambahRapatLabel">Tambah Rapat Baru</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('rapat.store') }}" method="POST" autocomplete="off">
        @csrf
        <div class="modal-body">
          @if ($errors->any() && session('from_modal') == 'tambah_rapat')
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
          @endif
          @include('rapat._form')
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

@if ($errors->any() && session('from_modal') == 'tambah_rapat')
@push('scripts')
<script>
$(document).ready(function(){
    $('#modalTambahRapat').modal('show');
});
</script>
@endpush
@endif

@endsection

@push('scripts')
<!-- Bootstrap JS & jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
@endpush
