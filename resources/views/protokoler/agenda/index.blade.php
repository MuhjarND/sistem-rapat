@extends('layouts.app')

@section('title','Agenda Pimpinan')

@section('content')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-1">Agenda Pimpinan</h3>
      <div class="text-muted">Kirim notifikasi WA ke pimpinan terpilih.</div>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @elseif(session('warning'))
    <div class="alert alert-warning">{{ session('warning') }}</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card mb-4">
    <div class="card-body">
      <form action="{{ route('agenda-pimpinan.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
          <label>Nomor Naskah Dinas</label>
          <input type="text" name="nomor_naskah" class="form-control" value="{{ old('nomor_naskah') }}" placeholder="contoh: 1070/KPTA.W31-A/UND.OT1.2/X/2025">
        </div>

        <div class="form-row">
          <div class="form-group col-md-4">
            <label>Tanggal</label>
            <input type="date" name="tanggal" class="form-control" value="{{ old('tanggal') }}" required>
          </div>
          <div class="form-group col-md-4">
            <label>Waktu</label>
            <input type="time" name="waktu" class="form-control" value="{{ old('waktu') }}" required>
          </div>
          <div class="form-group col-md-4">
            <label>Tempat</label>
            <input type="text" name="tempat" class="form-control" value="{{ old('tempat') }}" required>
          </div>
        </div>

        <div class="form-group">
          <label>Peserta penerima (multi)</label>
          <select name="penerima_ids[]" class="form-control js-select2" multiple data-placeholder="Pilih peserta penerima" required>
            @foreach($daftar_penerima as $p)
              <option value="{{ $p->id }}" {{ in_array($p->id, old('penerima_ids', [])) ? 'selected' : '' }}>
                {{ $p->name }} @if(!empty($p->jabatan)) - {{ $p->jabatan }} @endif
              </option>
            @endforeach
          </select>
          <small class="text-muted">Pilih peserta yang akan menerima info agenda.</small>
        </div>

        <div class="form-group">
          <label>Judul Agenda</label>
          <input type="text" name="judul" class="form-control" value="{{ old('judul') }}" required>
        </div>

        <div class="form-group">
          <label>Yang menghadiri</label>
          <div class="form-control" style="height:auto;min-height:42px;">
            <small class="text-muted">Otomatis diisi dari daftar penerima yang dipilih.</small>
          </div>
        </div>

        <div class="form-group">
          <label>Seragam yang digunakan</label>
          <input type="text" name="seragam" class="form-control" value="{{ old('seragam') }}" placeholder="Misal: PDH Hitam Putih">
        </div>

        <div class="form-group">
          <label>Link lampiran undangan (opsional)</label>
          <input type="url" name="lampiran_url" class="form-control" value="{{ old('lampiran_url') }}" placeholder="https://contoh.com/undangan.pdf">
          <small class="text-muted">Tempel tautan file undangan (PDF/dokumen) yang sudah diunggah di tempat lain.</small>
        </div>

        <div class="d-flex justify-content-end">
          <button type="submit" class="btn btn-primary">Kirim Notifikasi</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header">Riwayat Kirim Terbaru</div>
    <div class="card-body">
      @if($agenda->isEmpty())
        <div class="text-muted">Belum ada kiriman agenda.</div>
      @else
      <div class="table-responsive">
        <table class="table table-striped mb-0">
          <thead>
            <tr>
              <th>Agenda</th>
              <th>Tanggal</th>
              <th>Waktu</th>
              <th>Tempat</th>
              <th>Penerima</th>
              <th>Lampiran</th>
              <th>Dibuat</th>
            </tr>
          </thead>
          <tbody>
            @foreach($agenda as $a)
              <tr>
                <td>
                  <div class="font-weight-bold">{{ $a->judul }}</div>
                  @php $pjs = isset($a->penerima_json) ? (json_decode($a->penerima_json,true) ?: []) : []; @endphp
                  @if(count($pjs))
                    <div class="text-muted small">{{ count($pjs) }} penerima</div>
                  @endif
                </td>
                <td>{{ \Carbon\Carbon::parse($a->tanggal)->translatedFormat('d M Y') }}</td>
                <td>{{ $a->waktu }}</td>
                <td>{{ $a->tempat }}</td>
                <td>
                  @php $pjs = isset($a->penerima_json) ? (json_decode($a->penerima_json,true) ?: []) : []; @endphp
                  @if(count($pjs))
                    {{ count($pjs) }} orang
                  @else
                    -
                  @endif
                </td>
                <td>
                  @if($a->lampiran_path)
                    <a href="{{ asset($a->lampiran_path) }}" target="_blank">Lampiran</a>
                  @else
                    -
                  @endif
                </td>
                <td>{{ \Carbon\Carbon::parse($a->created_at)->format('d/m H:i') }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @endif
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  $(function(){
    $('.js-select2').select2({ width:'100%', allowClear:true, placeholder:'Pilih' });
  });
</script>
@endpush
