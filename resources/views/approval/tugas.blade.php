{{-- resources/views/approval/tugas.blade.php --}}
@extends('layouts.app')
@section('title','Monitoring Tugas Peserta')

@section('style')
<style>
  .card-dark{
    background:linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,.015));
    border:1px solid rgba(255,255,255,.12);
    border-radius:16px;
    box-shadow:var(--shadow);
    color:var(--text);
  }
  .summary-card{
    border-radius:16px;
    padding:16px;
    background:linear-gradient(180deg,rgba(79,70,229,.15),rgba(13,18,35,.8));
    border:1px solid rgba(255,255,255,.12);
    color:#fff;
    box-shadow:var(--shadow);
  }
  .summary-card .label{font-size:.85rem;opacity:.85}
  .summary-card .value{font-size:1.9rem;font-weight:800;letter-spacing:.2px}
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
</style>
@endsection

@section('content')
<div class="container">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-1">Monitoring Tugas Peserta</h3>
      <small class="text-muted">Melihat status seluruh tugas notulensi yang diberikan kepada peserta.</small>
    </div>
    <a href="{{ route('approval.dashboard') }}" class="btn btn-outline-light btn-sm mt-2 mt-md-0">
      <i class="fas fa-arrow-left mr-1"></i> Kembali ke Dashboard
    </a>
  </div>

  <div class="row mb-3">
    <div class="col-md-3 mb-2">
      <div class="summary-card">
        <div class="label">Total Tugas</div>
        <div class="value">{{ $summary['total'] ?? 0 }}</div>
      </div>
    </div>
    <div class="col-md-3 mb-2">
      <div class="summary-card">
        <div class="label">Pending</div>
        <div class="value">{{ $summary['pending'] ?? 0 }}</div>
      </div>
    </div>
    <div class="col-md-3 mb-2">
      <div class="summary-card">
        <div class="label">Proses</div>
        <div class="value">{{ $summary['proses'] ?? 0 }}</div>
      </div>
    </div>
    <div class="col-md-3 mb-2">
      <div class="summary-card">
        <div class="label">Selesai</div>
        <div class="value">{{ $summary['done'] ?? 0 }}</div>
      </div>
    </div>
  </div>

  <div class="card card-dark mb-3">
    <div class="card-body">
      <form method="get" class="row g-2">
        <div class="col-md-3">
          <input type="text" name="q" value="{{ $filter['q'] ?? '' }}" class="form-control" placeholder="Cari peserta / rapat / uraian">
        </div>
        <div class="col-md-2">
          <select name="status" class="form-control">
            <option value="">Semua Status</option>
            <option value="pending" {{ ($filter['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="proses" {{ ($filter['status'] ?? '') === 'proses' ? 'selected' : '' }}>Proses</option>
            <option value="done" {{ ($filter['status'] ?? '') === 'done' ? 'selected' : '' }}>Selesai</option>
          </select>
        </div>
        <div class="col-md-2">
          <select name="eviden" class="form-control">
            <option value="">Semua Eviden</option>
            <option value="ada" {{ ($filter['eviden'] ?? '') === 'ada' ? 'selected' : '' }}>Ada Eviden</option>
            <option value="belum" {{ ($filter['eviden'] ?? '') === 'belum' ? 'selected' : '' }}>Belum Ada</option>
          </select>
        </div>
        <div class="col-md-2">
          <input type="number" name="rapat" value="{{ $filter['rapat'] ?? '' }}" class="form-control" placeholder="ID Rapat">
        </div>
        <div class="col-md-2">
          <input type="number" name="user" value="{{ $filter['user'] ?? '' }}" class="form-control" placeholder="ID Peserta">
        </div>
        <div class="col-md-1">
          <select name="per_page" class="form-control">
            @foreach([10,15,25,50] as $p)
              <option value="{{ $p }}" {{ ($filter['per_page'] ?? 15) == $p ? 'selected' : '' }}>{{ $p }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-12 mt-2">
          <button class="btn btn-primary btn-sm"><i class="fas fa-filter mr-1"></i> Terapkan Filter</button>
          <a href="{{ route('approval.tugas') }}" class="btn btn-outline-light btn-sm ml-2">Reset</a>
        </div>
      </form>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      {{ session('success') }}
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      {{ $errors->first() }}
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
  @endif

  <div class="card card-dark">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-dark table-hover mb-0">
          <thead>
            <tr>
              <th>Peserta</th>
              <th>Rapat</th>
              <th>Tugas</th>
              <th class="text-center">Target</th>
              <th class="text-center">Status</th>
              <th class="text-center">Eviden</th>
              <th class="text-center">Update</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse($tasks as $task)
              @php
                $badgeMap = [
                  'pending' => ['badge-status badge-pending', 'Pending'],
                  'proses' => ['badge-status badge-proses', 'Proses'],
                  'in_progress' => ['badge-status badge-proses', 'Proses'],
                  'done' => ['badge-status badge-done', 'Selesai'],
                ];
                $badge = $badgeMap[$task->status] ?? ['badge-status badge-pending', ucfirst($task->status)];
                $deadline = $task->tgl_penyelesaian ? \Carbon\Carbon::parse($task->tgl_penyelesaian)->isoFormat('D MMM Y') : '—';
              @endphp
              <tr>
                <td>
                  <div class="font-weight-bold">{{ $task->peserta_nama ?? '-' }}</div>
                  <small class="text-muted">{{ $task->peserta_unit ?? '-' }}</small>
                </td>
                <td>
                  <div class="font-weight-bold">{{ $task->rapat_judul }}</div>
                  <small class="text-muted">
                    {{ \Carbon\Carbon::parse($task->rapat_tanggal)->isoFormat('D MMM Y') }}
                    · {{ $task->waktu_mulai }} WIT · {{ $task->tempat }}
                  </small>
                </td>
                <td>
                  {!! $task->hasil_pembahasan !!}
                  @if($task->rekomendasi)
                    <div class="mt-1"><span class="badge badge-info">Rekomendasi</span> {!! $task->rekomendasi !!}</div>
                  @endif
                </td>
                <td class="text-center">{{ $deadline }}</td>
                <td class="text-center"><span class="{{ $badge[0] }}">{{ $badge[1] }}</span></td>
                <td class="text-center">
                  @if($task->eviden_path)
                    <a href="{{ asset($task->eviden_path) }}" target="_blank"><i class="fas fa-image mr-1"></i>Gambar</a><br>
                  @endif
                  @if($task->eviden_link)
                    <a href="{{ $task->eviden_link }}" target="_blank"><i class="fas fa-link mr-1"></i>Link</a>
                  @endif
                  @if(!$task->eviden_path && !$task->eviden_link)
                    <small class="text-muted">Belum ada</small>
                  @endif
                </td>
                <td class="text-center">
                  {{ $task->updated_at ? \Carbon\Carbon::parse($task->updated_at)->diffForHumans() : '—' }}
                </td>
                <td class="text-center">
                  @if(in_array($task->status, ['done']))
                    <small class="text-muted">Sudah selesai</small>
                  @else
                    <form method="POST" action="{{ route('approval.tugas.remind', $task->id) }}" onsubmit="return confirm('Kirim pengingat WA kepada {{ $task->peserta_nama }}?');">
                      @csrf
                      <button type="submit" class="btn btn-sm btn-outline-warning">
                        <i class="fas fa-paper-plane mr-1"></i> Kirim WA
                      </button>
                    </form>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted p-4">Belum ada tugas yang cocok dengan filter.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="p-3">
        {{ $tasks->links() }}
      </div>
    </div>
  </div>
</div>
@endsection
