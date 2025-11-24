@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">Daftar Jadwal Rapat Berkala</h3>
            <div class="text-muted">Rapat dengan frekuensi bulanan / triwulanan / tahunan.</div>
        </div>
        <div>
            <a href="{{ route('rapat.schedule.create') }}" class="btn btn-primary btn-sm mr-2">+ Buat Jadwal</a>
            <a href="{{ route('rapat.index') }}" class="btn btn-outline-secondary btn-sm">Kembali ke Rapat</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('rapat.schedule.index') }}">
                <div class="form-row align-items-end">
                    <div class="col-md-3 mb-2">
                        <label class="mb-1 text-muted">Jenis Jadwal</label>
                        <select name="schedule_type" class="form-control form-control-sm">
                            <option value="">Semua</option>
                            <option value="bulanan" {{ request('schedule_type')=='bulanan' ? 'selected' : '' }}>Bulanan</option>
                            <option value="triwulanan" {{ request('schedule_type')=='triwulanan' ? 'selected' : '' }}>Triwulanan</option>
                            <option value="tahunan" {{ request('schedule_type')=='tahunan' ? 'selected' : '' }}>Tahunan</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="mb-1 text-muted">Cari Judul/Nomor/Keterangan</label>
                        <input type="text" name="keyword" value="{{ request('keyword') }}" class="form-control form-control-sm" placeholder="cth: Triwulan 1 / September">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="mb-1 d-none d-md-block">&nbsp;</label>
                        <button class="btn btn-primary btn-sm btn-block">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if($rapatList->isEmpty())
                <div class="text-center text-muted">Belum ada rapat terjadwal.</div>
            @else
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Judul</th>
                            <th>Jadwal</th>
                            <th>Keterangan</th>
                            <th>Tanggal & Waktu</th>
                            <th>Tempat</th>
                            <th>Status Approval</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rapatList as $i => $r)
                        @php
                            $enqueued = !empty($r->approval_enqueued_at);
                            $label = $enqueued ? 'Sudah dikirim' : 'Belum dikirim';
                            $badge = $enqueued ? 'badge-success' : 'badge-warning';
                        @endphp
                        <tr>
                            <td>{{ $i+1 }}</td>
                            <td>
                                <div class="font-weight-bold">{{ $r->judul }}</div>
                                <div class="text-muted small">
                                    {{ $r->nama_kategori ?? 'Tanpa kategori' }}
                                </div>
                                <div class="text-muted small">Nomor: {{ $r->nomor_undangan ?? '—' }}</div>
                                <div class="text-muted small">Pembuat: {{ $r->nama_pembuat ?? '—' }}</div>
                            </td>
                            <td class="text-capitalize">{{ $r->schedule_type }}</td>
                            <td>{{ $r->schedule_label ?? '—' }}</td>
                            <td>
                                {{ \Carbon\Carbon::parse($r->tanggal)->translatedFormat('d M Y') }}
                                {{ $r->waktu_mulai }}
                            </td>
                            <td>{{ $r->tempat }}</td>
                            <td>
                                <span class="badge {{ $badge }}">{{ $label }}</span>
                            </td>
                            <td class="text-nowrap">
                                <a href="{{ route('rapat.show', $r->id) }}" class="btn btn-sm btn-outline-info mb-1">Detail</a>
                                @if(!$enqueued)
                                    <form action="{{ route('rapat.sendApproval', $r->id) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Kirim rapat ini ke approval?')">
                                        @csrf
                                        <button class="btn btn-sm btn-success mb-1" type="submit">
                                            <i class="fa fa-paper-plane"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
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
