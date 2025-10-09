{{-- resources/views/approval/dashboard.blade.php --}}
@extends('layouts.app')
@section('title','Approval Dashboard')

@section('style')
<style>
  .kpi-card{background:linear-gradient(180deg,rgba(255,255,255,.04),rgba(255,255,255,.02));border:1px solid var(--border);border-radius:14px;box-shadow:var(--shadow);padding:14px;color:var(--text)}
  .kpi-card .value{font-size:1.6rem;font-weight:800}
  .kpi-card .label{opacity:.85}
  .badge-soft{border-radius:999px;padding:.25rem .55rem;border:1px solid rgba(255,255,255,.25)}
  .badge-soft.green{background:rgba(34,197,94,.15)}
  .badge-soft.yellow{background:rgba(245,158,11,.15)}
  .badge-soft.indigo{background:rgba(79,70,229,.18)}
  .badge-soft.cyan{background:rgba(14,165,233,.18)}
  .doc-badge{font-weight:800;letter-spacing:.2px;border-radius:8px;padding:.12rem .4rem}
  .doc-undangan{background:#0ea5e9;color:#05293a}
  .doc-notulensi{background:#22c55e;color:#05341e}
  .doc-absensi{background:#f59e0b;color:#3a2705}
</style>
@endsection

@section('content')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Approval Dashboard</h3>
    <a href="{{ route('approval.pending') }}" class="btn btn-outline-light btn-sm">
      <i class="fas fa-list mr-1"></i> Lihat Semua Pending
    </a>
  </div>

  {{-- KPI --}}
  <div class="row">
    <div class="col-md-3 mb-3">
      <div class="kpi-card">
        <div class="label">Pending</div>
        <div class="value">{{ $metrics['pending_total'] }}</div>
        <span class="badge-soft indigo">Siap tanda tangan: {{ $metrics['open_total'] }}</span>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="kpi-card">
        <div class="label">Menunggu Tahap Sebelumnya</div>
        <div class="value">{{ $metrics['blocked_total'] }}</div>
        <span class="badge-soft yellow">Tertahan</span>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="kpi-card">
        <div class="label">Approved (30 hari)</div>
        <div class="value">{{ $metrics['approved_30d'] }}</div>
        <span class="badge-soft green">Terakhir</span>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="kpi-card">
        <div class="label">Pending per Jenis</div>
        <div class="d-flex flex-wrap" style="gap:.35rem">
          <span class="doc-badge doc-undangan">Undangan: {{ $metrics['by_type']['undangan'] }}</span>
          <span class="doc-badge doc-notulensi">Notulensi: {{ $metrics['by_type']['notulensi'] }}</span>
          <span class="doc-badge doc-absensi">Absensi: {{ $metrics['by_type']['absensi'] }}</span>
        </div>
      </div>
    </div>
  </div>

  {{-- BUTUH TINDAKAN ANDA --}}
  <div class="card mb-3">
    <div class="card-header"><b>Butuh Tindakan Anda (Siap Ditandatangani)</b></div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th style="width:8%">Jenis</th>
              <th>Rapat</th>
              <th style="width:14%">Tanggal</th>
              <th style="width:20%">Tempat</th>
              <th style="width:10%">Urutan</th>
              <th style="width:14%"></th>
            </tr>
          </thead>
          <tbody>
            @forelse($pendingOpen as $r)
              <tr>
                <td class="text-center">
                  @php
                    $cls = $r->doc_type==='undangan'?'doc-undangan':($r->doc_type==='notulensi'?'doc-notulensi':'doc-absensi');
                  @endphp
                  <span class="doc-badge {{ $cls }}">{{ ucfirst($r->doc_type) }}</span>
                </td>
                <td>
                  <div class="font-weight-bold">{{ $r->judul }}</div>
                  <small class="text-muted">No: {{ $r->nomor_undangan ?? '-' }}</small>
                </td>
                <td>{{ \Carbon\Carbon::parse($r->tanggal)->translatedFormat('d F Y') }}<br>
                  <small class="text-muted">{{ $r->waktu_mulai }}</small>
                </td>
                <td>{{ $r->tempat }}</td>
                <td class="text-center">Step {{ $r->order_index }}</td>
                <td class="text-right">
                  <a href="{{ route('approval.sign', $r->sign_token) }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-pen-nib mr-1"></i> Tanda Tangani
                  </a>
                </td>
              </tr>
            @empty
              <tr><td colspan="6" class="text-center text-muted p-3">Tidak ada yang perlu ditandatangani saat ini.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- MENUNGGU TAHAP SEBELUMNYA --}}
  <div class="card mb-3">
    <div class="card-header"><b>Menunggu Tahap Sebelumnya</b></div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th style="width:8%">Jenis</th>
              <th>Rapat</th>
              <th style="width:14%">Tanggal</th>
              <th style="width:20%">Tempat</th>
              <th style="width:10%">Urutan Anda</th>
              <th style="width:14%">Status</th>
            </tr>
          </thead>
          <tbody>
            @forelse($pendingBlocked as $r)
              @php
                $cls = $r->doc_type==='undangan'?'doc-undangan':($r->doc_type==='notulensi'?'doc-notulensi':'doc-absensi');
              @endphp
              <tr>
                <td class="text-center">
                  <span class="doc-badge {{ $cls }}">{{ ucfirst($r->doc_type) }}</span>
                </td>
                <td>
                  <div class="font-weight-bold">{{ $r->judul }}</div>
                  <small class="text-muted">No: {{ $r->nomor_undangan ?? '-' }}</small>
                </td>
                <td>{{ \Carbon\Carbon::parse($r->tanggal)->translatedFormat('d F Y') }}<br>
                  <small class="text-muted">{{ $r->waktu_mulai }}</small>
                </td>
                <td>{{ $r->tempat }}</td>
                <td class="text-center">Step {{ $r->order_index }}</td>
                <td><span class="badge-soft yellow">Menunggu approver sebelumnya</span></td>
              </tr>
            @empty
              <tr><td colspan="6" class="text-center text-muted p-3">Tidak ada yang tertahan.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- RIWAYAT APPROVED --}}
  <div class="card">
    <div class="card-header"><b>Riwayat Persetujuan (30 Hari Terakhir)</b></div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th style="width:8%">Jenis</th>
              <th>Rapat</th>
              <th style="width:14%">Tgl Rapat</th>
              <th style="width:20%">Tempat</th>
              <th style="width:12%">Step</th>
              <th style="width:18%">Approved Pada</th>
            </tr>
          </thead>
          <tbody>
            @forelse($recentApproved as $r)
              @php
                $cls = $r->doc_type==='undangan'?'doc-undangan':($r->doc_type==='notulensi'?'doc-notulensi':'doc-absensi');
              @endphp
              <tr>
                <td class="text-center"><span class="doc-badge {{ $cls }}">{{ ucfirst($r->doc_type) }}</span></td>
                <td class="font-weight-bold">{{ $r->judul }}</td>
                <td>{{ \Carbon\Carbon::parse($r->tanggal)->translatedFormat('d F Y') }}</td>
                <td>{{ $r->tempat }}</td>
                <td class="text-center">Step {{ $r->order_index }}</td>
                <td>{{ \Carbon\Carbon::parse($r->signed_at)->translatedFormat('d F Y H:i') }}</td>
              </tr>
            @empty
              <tr><td colspan="6" class="text-center text-muted p-3">Belum ada riwayat.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
