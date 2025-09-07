@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between mb-3">
        <h3>Daftar Rapat</h3>
        @if(Auth::user()->role == 'admin')
            <!-- Tombol Modal Tambah -->
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalTambahRapat">
                + Tambah Rapat
            </button>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- FILTER --}}
    <form method="GET" action="{{ route('rapat.index') }}" class="mb-3">
        <div class="row align-items-end">
            <div class="col-md-3">
                <label class="mb-1">Kategori Rapat</label>
                <select name="kategori" class="form-control form-control-sm">
                    <option value="">Semua Kategori</option>
                    @foreach($daftar_kategori as $kategori)
                        <option value="{{ $kategori->id }}" {{ request('kategori') == $kategori->id ? 'selected' : '' }}>
                            {{ $kategori->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="mb-1">Tanggal</label>
                <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label class="mb-1">Cari Judul/Nomor</label>
                <input type="text" name="keyword" value="{{ request('keyword') }}" class="form-control form-control-sm" placeholder="Cari...">
            </div>
            <div class="col-md-2">
                <button class="btn btn-info btn-block btn-sm">Filter</button>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped table-sm m-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nomor Undangan</th>
                        <th>Judul</th>
                        <th>Kategori</th>
                        <th>Tgl & Waktu</th>
                        <th>Tempat</th>
                        <th>Dibuat Oleh</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($daftar_rapat as $no => $rapat)
                    <tr>
                        <td>{{ $daftar_rapat->firstItem() + $no }}</td>
                        <td>{{ $rapat->nomor_undangan }}</td>
                        <td>{{ $rapat->judul }}</td>
                        <td>{{ $rapat->nama_kategori ?? '-' }}</td>
                        <td>
                            {{ \Carbon\Carbon::parse($rapat->tanggal)->format('d M Y') }}
                            <span class="text-muted">{{ $rapat->waktu_mulai }}</span>
                        </td>
                        <td>{{ $rapat->tempat }}</td>
                        <td>{{ $rapat->nama_pembuat ?? '-' }}</td>
                        <td>
                            <span class="badge
                                @if($rapat->status_label == 'Akan Datang') badge-info
                                @elseif($rapat->status_label == 'Berlangsung') badge-success
                                @elseif($rapat->status_label == 'Selesai') badge-secondary
                                @elseif($rapat->status_label == 'Dibatalkan') badge-danger
                                @endif">
                                {{ $rapat->status_label }}
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center">
                                <a href="{{ route('rapat.show', $rapat->id) }}" 
                                   class="btn btn-info btn-sm mr-1" 
                                   title="Detail">
                                    <i class="fa fa-eye"></i>
                                </a>

                                @if(Auth::user()->role == 'admin')
                                    <!-- Tombol Modal Edit -->
                                    <button type="button" 
                                            class="btn btn-warning btn-sm mr-1" 
                                            data-toggle="modal" 
                                            data-target="#modalEditRapat-{{ $rapat->id }}" 
                                            title="Edit">
                                        <i class="fa fa-edit"></i>
                                    </button>

                                    <form action="{{ route('rapat.destroy', $rapat->id) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('Hapus rapat ini?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-danger btn-sm" title="Hapus">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center">Belum ada data rapat.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-3">
        {{ $daftar_rapat->links() }}
    </div>
</div>

<!-- Modal Tambah Rapat -->
<div class="modal fade" id="modalTambahRapat" tabindex="-1" role="dialog" aria-labelledby="tambahRapatLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content modal-solid">
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
          @include('rapat._form', [
              'rapat' => null,
              'peserta_terpilih' => [],
              'daftar_kategori' => $daftar_kategori,
              'daftar_pimpinan' => $daftar_pimpinan,
              'daftar_peserta' => $daftar_peserta,
              'dropdownParentId' => '#modalTambahRapat',
              'pesertaWrapperId' => 'peserta-wrapper-tambah'
          ])
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Edit untuk Setiap Rapat -->
@foreach($daftar_rapat as $rapat)
<div class="modal fade" id="modalEditRapat-{{ $rapat->id }}" tabindex="-1" role="dialog" aria-labelledby="editRapatLabel-{{ $rapat->id }}" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content modal-solid">
      <div class="modal-header">
        <h5 class="modal-title" id="editRapatLabel-{{ $rapat->id }}">Edit Rapat: {{ $rapat->judul }}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('rapat.update', $rapat->id) }}" method="POST" autocomplete="off">
        @csrf
        @method('PUT')
        <div class="modal-body">
          @include('rapat._form', [
              'rapat' => $rapat,
              'peserta_terpilih' => $rapat->peserta_terpilih ?? [],
              'daftar_kategori' => $daftar_kategori,
              'daftar_pimpinan' => $daftar_pimpinan,
              'daftar_peserta' => $daftar_peserta,
              'dropdownParentId' => '#modalEditRapat-' . $rapat->id,
              'pesertaWrapperId' => 'peserta-wrapper-edit-' . $rapat->id
          ])
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endforeach

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
