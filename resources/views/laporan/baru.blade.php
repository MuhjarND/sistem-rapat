﻿@extends('layouts.app')
@section('title','Laporan Rapat (Bulan Berjalan)')

@section('content')
<div class="container">
  <h3 class="mb-3">Laporan Rapat (Undangan - Absensi - Notulensi) - Bulan Berjalan</h3>

  <form class="card mb-3" method="GET" action="{{ route('laporan.baru') }}">
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
      <div class="mt-3 d-flex gap-2">
        <button class="btn btn-primary mr-2">Terapkan Filter</button>
        <a href="{{ route('laporan.cetak', request()->all()) }}" class="btn btn-success" target="_blank">Cetak PDF</a>
      </div>
    </div>
  </form>

  <div class="card">
    <div class="card-body p-0">
      <table class="table table-striped table-hover table-sm">
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
              <td>{{ \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') }} {{ \App\Helpers\TimeHelper::short($r->waktu_mulai) }}</td>
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
            <tr><td colspan="7" class="text-center text-muted p-4">Tidak ada data bulan ini.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection


