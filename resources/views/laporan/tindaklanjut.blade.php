@extends('layouts.app')
@section('title','Laporan Tindak Lanjut')

@push('style')
<style>
  .card-dark{
    background:linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,.015));
    border:1px solid rgba(255,255,255,.12);
    border-radius:16px;
    box-shadow:var(--shadow);
    color:var(--text);
  }
  .table-dark th{
    background:rgba(79,70,229,.18);
    border-color:rgba(255,255,255,.08);
  }
  .table-dark td{
    border-color:rgba(255,255,255,.04);
    vertical-align:middle;
  }
  .badge-status{font-weight:700;font-size:.78rem;padding:.2rem .6rem;border-radius:999px}
  .badge-pending{background:rgba(148,163,184,.35);color:#fff}
  .badge-proses{background:rgba(245,158,11,.35);color:#fff}
  .badge-done{background:rgba(34,197,94,.35);color:#fff}
  .tag-pill{
    display:inline-block; padding:.18rem .55rem; border-radius:999px;
    background:rgba(99,102,241,.18); border:1px solid rgba(99,102,241,.35);
    color:#e5e7eb; font-weight:700; font-size:.78rem;
  }
  .text-faint{color:#a5b4fc;}
</style>
@endpush

@section('content')
<div class="container">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-1">Laporan Tindak Lanjut</h3>
      <small class="text-muted">Rekap tindak lanjut & eviden tugas notulensi.</small>
    </div>
  </div>

  <div class="card card-dark mb-3">
    <div class="card-body">
      <form method="get" class="row g-2">
        <div class="col-md-4">
          <input type="text" name="q" value="{{ $filter['q'] ?? '' }}" class="form-control" placeholder="Cari rapat/peserta/uraian/catatan">
        </div>
        <div class="col-md-3">
          <select name="status" class="form-control">
            <option value="">Semua Status</option>
            <option value="pending" {{ ($filter['status'] ?? '')==='pending' ? 'selected' : '' }}>Pending</option>
            <option value="proses" {{ ($filter['status'] ?? '')==='proses' ? 'selected' : '' }}>Proses</option>
            <option value="in_progress" {{ ($filter['status'] ?? '')==='in_progress' ? 'selected' : '' }}>Proses (in_progress)</option>
            <option value="done" {{ ($filter['status'] ?? '')==='done' ? 'selected' : '' }}>Selesai</option>
          </select>
        </div>
        <div class="col-md-2">
          <select name="per_page" class="form-control">
            @foreach([10,15,25,50] as $p)
              <option value="{{ $p }}" {{ ($filter['per_page'] ?? 15)==$p ? 'selected' : '' }}>{{ $p }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <button class="btn btn-primary btn-sm"><i class="fas fa-filter mr-1"></i> Terapkan Filter</button>
          <a href="{{ route('laporan.tindaklanjut') }}" class="btn btn-outline-light btn-sm ml-2">Reset</a>
        </div>
      </form>
    </div>
  </div>

  @php
    // Group per rapat agar tidak dobel-dobel
    $grouped = $rows->groupBy('rapat_id');
  @endphp

  <div class="card card-dark">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-dark table-hover mb-0">
          <thead>
            <tr>
              <th style="width:60px;">No.</th>
              <th>Rapat</th>
              <th class="text-center" style="width:120px;">Cetak</th>
              <th>Rekomendasi & Tindak Lanjut</th>
              <th class="text-center">Update</th>
            </tr>
          </thead>
          <tbody>
            @php $counter = ($rows->firstItem() ?? 0); @endphp
            @forelse($grouped as $rapatId => $items)
              @php
                $row  = $items->first();
                $counter++;
                $latestUpdate = $items->max('updated_at');
              @endphp
              <tr>
                <td class="text-center">{{ $counter }}</td>
                <td>
                  <div class="font-weight-bold">{{ $row->rapat_judul }}</div>
                  <small class="text-faint">
                    {{ \Carbon\Carbon::parse($row->rapat_tanggal)->isoFormat('D MMM Y') }} · {{ $row->waktu_mulai }} WIT · {{ $row->tempat }}
                  </small>
                </td>
                <td class="text-center">
                  <a class="btn btn-sm btn-primary" target="_blank" href="{{ route('laporan.tindaklanjut.cetak', $row->rapat_id) }}">
                    <i class="fas fa-print mr-1"></i> Cetak
                  </a>
                </td>
                <td>
                  @foreach($items as $it)
                    @php
                      $badgeMap = [
                        'pending' => ['badge-status badge-pending','Pending'],
                        'proses' => ['badge-status badge-proses','Proses'],
                        'in_progress' => ['badge-status badge-proses','Proses'],
                        'done' => ['badge-status badge-done','Selesai'],
                      ];
                      $badge = $badgeMap[$it->status] ?? ['badge-status badge-pending', ucfirst($it->status ?? '-')];
                      $rekom = trim(preg_replace('/\s+/', ' ', str_replace('&nbsp;',' ', strip_tags($it->rekomendasi ?? ''))));
                      $tindak = trim(preg_replace('/\s+/', ' ', str_replace('&nbsp;',' ', strip_tags($it->eviden_note ?? ''))));
                    @endphp
                    <div class="mb-2 p-2" style="background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.08); border-radius:10px;">
                      <div class="d-flex justify-content-between">
                        <span class="tag-pill">#{{ $it->no_rekom ?? '-' }}</span>
                        <span class="{{ $badge[0] }}">{{ $badge[1] }}</span>
                      </div>
                      <div class="mt-1 text-muted small">{{ $it->peserta_nama ?? '-' }}</div>
                      @if($rekom)
                        <div class="mt-1"><strong>Rekomendasi:</strong> {{ $rekom }}</div>
                      @endif
                      <div class="mt-1"><strong>Tindak lanjut:</strong> {{ $tindak ?: '-' }}</div>
                      <div class="mt-1">
                        <strong>Eviden:</strong>
                        @if(!empty($it->eviden_path))
                          <a href="{{ asset($it->eviden_path) }}" target="_blank"><i class="fas fa-image mr-1"></i>Gambar</a>
                        @endif
                        @if(!empty($it->eviden_link))
                          <a href="{{ $it->eviden_link }}" target="_blank" class="ml-1"><i class="fas fa-link mr-1"></i>Link</a>
                        @endif
                        @if(empty($it->eviden_path) && empty($it->eviden_link))
                          <span class="text-muted">Belum ada eviden.</span>
                        @endif
                      </div>
                    </div>
                  @endforeach
                </td>
                <td class="text-center">{{ $latestUpdate ? \Carbon\Carbon::parse($latestUpdate)->diffForHumans() : '-' }}</td>
              </tr>
            @empty
              <tr><td colspan="6" class="text-center text-muted p-4">Belum ada data tindak lanjut.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="p-3">
        {{ $rows->links() }}
      </div>
    </div>
  </div>
</div>
@endsection
