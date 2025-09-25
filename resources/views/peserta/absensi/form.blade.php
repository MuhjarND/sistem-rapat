@extends('layouts.app')
@section('title','Konfirmasi Kehadiran')

@section('content')
<div class="card">
  <div class="card-header"><strong>Konfirmasi Kehadiran</strong></div>
  <div class="card-body">
    <p class="mb-2"><b>Rapat:</b> {{ $rapat->judul }}</p>
    <p class="text-muted">
      {{ \Carbon\Carbon::parse($rapat->tanggal)->isoFormat('dddd, D MMMM Y') }}
      • {{ $rapat->waktu_mulai }} WIT • {{ $rapat->tempat }}
    </p>

    @if($absensi)
      <div class="alert alert-success">
        Absensi Anda: <b>{{ strtoupper($absensi->status) }}</b>
        @if($absensi->waktu_absen) pada {{ \Carbon\Carbon::parse($absensi->waktu_absen)->format('d/m/Y H:i') }} @endif
      </div>
    @endif

    <form action="{{ route('absensi.isi') }}" method="post" class="mt-3">
      @csrf
      <input type="hidden" name="id_rapat" value="{{ $rapat->id }}">
      <div class="form-group">
        <label>Status kehadiran</label>
        <select name="status" class="custom-select" required>
          <option value="hadir" {{ ($absensi->status ?? '')==='hadir'?'selected':'' }}>Hadir</option>
          <option value="izin"  {{ ($absensi->status ?? '')==='izin'?'selected':'' }}>Izin</option>
          <option value="alfa"  {{ ($absensi->status ?? '')==='alfa'?'selected':'' }}>Tidak Hadir</option>
        </select>
      </div>
      <button class="btn btn-primary">Simpan</button>
    </form>
  </div>
</div>
@endsection
