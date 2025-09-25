@extends('layouts.app')

@section('title','Rapat Saya')

@section('content')
<div class="card mb-4">
    <div class="card-header d-flex align-items-center">
        <span><i class="fas fa-calendar-alt mr-2"></i> Daftar Rapat</span>
        <a href="{{ route('peserta.dashboard') }}" class="ml-auto btn btn-sm btn-outline-light">
            <i class="fas fa-home mr-1"></i> Dashboard
        </a>
    </div>
    <div class="card-body">
        <form method="get" class="mb-3">
            <div class="form-row">
                <div class="col-md-3 mb-2">
                    <label class="mb-1">Jenis</label>
                    <select name="jenis" class="custom-select">
                        <option value="upcoming" {{ ($filter['jenis']??'')==='upcoming'?'selected':'' }}>Akan Datang</option>
                        <option value="past"     {{ ($filter['jenis']??'')==='past'?'selected':'' }}>Sudah Berlalu</option>
                        <option value="all"      {{ ($filter['jenis']??'')==='all'?'selected':'' }}>Semua</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="mb-1">Kata Kunci</label>
                    <input type="text" name="q" value="{{ $filter['q']??'' }}" class="form-control" placeholder="Judul / Nomor / Tempat">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="mb-1">Dari Tanggal</label>
                    <input type="date" name="from" value="{{ $filter['from']??'' }}" class="form-control">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="mb-1">Sampai</label>
                    <input type="date" name="to" value="{{ $filter['to']??'' }}" class="form-control">
                </div>
                <div class="col-md-2 mb-2 d-flex align-items-end">
                    <button class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i> Filter</button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th style="min-width:220px;">Judul</th>
                    <th>Nomor</th>
                    <th class="text-center">Tanggal</th>
                    <th class="text-center">Waktu</th>
                    <th>Tempat</th>
                    <th class="text-center">Absensi</th>
                    <th class="text-center">Notulensi</th>
                    <th class="text-center" style="width:110px;">Aksi</th>
                </tr>
                </thead>
                <tbody>
                @forelse($rapat as $r)
                    <tr>
                        <td><a class="text-light" href="{{ url('rapat/'.$r->id) }}">{{ $r->judul }}</a></td>
                        <td>{{ $r->nomor_undangan ?? '—' }}</td>
                        <td class="text-center">{{ \Carbon\Carbon::parse($r->tanggal)->isoFormat('D MMM Y') }}</td>
                        <td class="text-center">{{ $r->waktu_mulai }} WIT</td>
                        <td>{{ $r->tempat }}</td>
                        <td class="text-center">
                            @php $s = $r->status_absensi; @endphp
                            @if($s === 'hadir')
                                <span class="badge badge-success">HADIR</span>
                            @elseif($s === 'izin')
                                <span class="badge badge-warning">IZIN</span>
                            @elseif($s === 'alfa')
                                <span class="badge badge-danger">ALFA</span>
                            @else
                                <span class="badge badge-secondary">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($r->id_notulensi)
                                <a href="{{ url('notulensi/'.$r->id_notulensi) }}" class="badge badge-info">Lihat</a>
                            @else
                                <span class="badge badge-secondary">Belum</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ url('rapat/'.$r->id) }}" class="btn btn-sm btn-outline-light">
                                Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted">Tidak ada data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-end">
            {{ $rapat->links() }}
        </div>
    </div>
</div>
@endsection
