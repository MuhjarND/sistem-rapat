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
        {{-- Filter --}}
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

        {{-- Tabel --}}
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th style="min-width:240px;">Judul</th>
                    <th>Nomor</th>
                    <th class="text-center" style="min-width:160px;">Tanggal & Waktu</th>
                    <th>Tempat</th>
                    <th class="text-center">Absensi</th>
                    <th class="text-center">Notulensi</th>
                    <th class="text-center" style="width:120px;">Aksi</th>
                </tr>
                </thead>
                <tbody>
                @forelse($rapat as $r)
                    <tr>
                        <td>
                            <a class="text-light" href="{{ route('peserta.rapat.show', $r->id) }}">{{ $r->judul }}</a>
                            @if(!empty($r->nama_kategori))
                                <div class="text-muted small">{{ $r->nama_kategori }}</div>
                            @endif
                        </td>
                        <td>{{ $r->nomor_undangan ?? '—' }}</td>

                        {{-- Gabung tanggal & waktu --}}
                        <td class="text-center">
                            {{ \Carbon\Carbon::parse($r->tanggal)->isoFormat('D/MM/Y') }}
                            <div class="text-muted small">{{ $r->waktu_mulai }} WIT</div>
                        </td>

                        <td>{{ $r->tempat }}</td>

                        {{-- Absensi: badge jika sudah, tombol Konfirmasi jika belum (prioritas ke absensi.scan bila ada token_qr) --}}
                        <td class="text-center">
                            @php $s = $r->status_absensi; @endphp
                            @if($s === 'hadir')
                                <span class="badge badge-success">HADIR</span>
                            @elseif($s === 'izin')
                                <span class="badge badge-warning">IZIN</span>
                            @elseif($s === 'alfa')
                                <span class="badge badge-danger">ALFA</span>
                            @else
                                @if(!empty($r->token_qr))
                                    <a href="{{ route('absensi.scan', $r->token_qr) }}"
                                       class="btn btn-sm btn-outline-light">
                                        Konfirmasi
                                    </a>
                                @else
                                    <a href="{{ route('peserta.absensi', $r->id) }}"
                                       class="btn btn-sm btn-outline-light">
                                        Konfirmasi
                                    </a>
                                @endif
                            @endif
                        </td>

                        {{-- Notulensi: tombol Lihat jika ada, sama seperti di dashboard --}}
                        <td class="text-center">
                            @if(!empty($r->id_notulensi))
                                <a href="{{ route('peserta.notulensi.show', $r->id) }}" class="btn btn-sm btn-info">Lihat</a>
                            @else
                                <span class="badge badge-secondary">Belum ada</span>
                            @endif
                        </td>

                        {{-- Aksi --}}
                        <td class="text-center">
                            <a href="{{ route('peserta.rapat.show', $r->id) }}" class="btn btn-sm btn-outline-light">
                                Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">Tidak ada data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-end mt-3">
            {{ $rapat->links() }}
        </div>
    </div>
</div>
@endsection
