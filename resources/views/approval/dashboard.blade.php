{{-- resources/views/approval/dashboard.blade.php --}}
@extends('layouts.app')
@section('title','Approval Dashboard')

@section('style')
<style>
  /* KPI grid */
  .kpi-card{background:linear-gradient(180deg,rgba(255,255,255,.05),rgba(255,255,255,.02));border:1px solid var(--border);border-radius:16px;box-shadow:var(--shadow);padding:16px 18px;color:var(--text)}
  .kpi-card .label{opacity:.9;font-size:.85rem}
  .kpi-card .value{font-size:1.85rem;font-weight:800;letter-spacing:.2px;line-height:1.2}
  .kpi-foot{display:flex;align-items:center;gap:.5rem;margin-top:6px}
  .chip{border-radius:999px;padding:.18rem .55rem;border:1px solid rgba(255,255,255,.22);font-size:.72rem;font-weight:800;letter-spacing:.2px}
  .chip.indigo{background:rgba(99,102,241,.18)}
  .chip.yellow{background:rgba(245,158,11,.18)}
  .chip.green{background:rgba(34,197,94,.18)}
  .chip.cyan{background:rgba(14,165,233,.18)}

  /* Doc badges */
  .doc-badge{font-weight:800;letter-spacing:.2px;border-radius:8px;padding:.12rem .45rem;font-size:.76rem}
  .doc-undangan{background:#0ea5e9;color:#05293a}
  .doc-notulensi{background:#22c55e;color:#05341e}
  .doc-absensi{background:#f59e0b;color:#3a2705}

  /* Stacked progress (Pending per jenis) */
  .stacked-bar{display:flex;height:12px;border-radius:999px;overflow:hidden;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15)}
  .stacked-bar > span{display:block;height:100%}
  .seg-undangan{background:rgba(14,165,233,.85)}
  .seg-notulensi{background:rgba(34,197,94,.85)}
  .seg-absensi{background:rgba(245,158,11,.9)}

  .legend{display:flex;flex-wrap:wrap;gap:.4rem .8rem}
  .legend .dot{width:10px;height:10px;border-radius:2px;display:inline-block;margin-right:.35rem}
  .dot-undangan{background:rgba(14,165,233,.85)}
  .dot-notulensi{background:rgba(34,197,94,.85)}
  .dot-absensi{background:rgba(245,158,11,.9)}

  .card h6{margin:0}
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

  {{-- ===== KPI ROW ===== --}}
  <div class="row">
    <div class="col-md-3 mb-3">
      <div class="kpi-card">
        <div class="label">Menunggu Aksi Anda</div>
        <div class="value">{{ $metrics['pending_total'] }}</div>
        <div class="kpi-foot">
          <span class="chip indigo">Siap TTD: {{ $metrics['open_total'] }}</span>
          <span class="chip yellow">Tertahan: {{ $metrics['blocked_total'] }}</span>
        </div>
      </div>
    </div>

    <div class="col-md-3 mb-3">
      <div class="kpi-card">
        <div class="label">Dokumen Disetujui (30 Hari)</div>
        <div class="value">{{ $metrics['approved_30d'] }}</div>
        <div class="kpi-foot"><span class="chip green">Terbaru</span></div>
      </div>
    </div>

    <div class="col-md-3 mb-3">
      <div class="kpi-card">
        <div class="label">Rapat yang Diikuti</div>
        <div class="value">{{ $metrics['meetings_joined'] }}</div>
        <div class="kpi-foot"><span class="chip cyan">Sebagai Peserta</span></div>
      </div>
    </div>

    <div class="col-md-3 mb-3">
      <div class="kpi-card">
        <div class="label">Total Dokumen Disetujui</div>
        <div class="value">{{ $metrics['docs_approved_total'] }}</div>
        <div class="kpi-foot">
          <span class="doc-badge doc-undangan">U: {{ $metrics['by_type']['undangan'] }}</span>
          <span class="doc-badge doc-notulensi">N: {{ $metrics['by_type']['notulensi'] }}</span>
          <span class="doc-badge doc-absensi">A: {{ $metrics['by_type']['absensi'] }}</span>
        </div>
      </div>
    </div>
  </div>

  {{-- ===== Pending per Jenis (modern) ===== --}}
  <div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between">
      <h6>Pending per Jenis Dokumen</h6>
      <small class="text-muted">Total: {{ $metrics['pending_total'] }}</small>
    </div>
    <div class="card-body">
      <div class="stacked-bar mb-2">
        @php
          $pct = $metrics['pending_pct'];
          $u = max(0,$pct['undangan']);
          $n = max(0,$pct['notulensi']);
          $a = max(0,$pct['absensi']);
          $sum = $u+$n+$a;
          if ($sum !== 100 && $metrics['pending_total']>0){
            $diff = 100 - $sum;
            $maxKey = 'undangan';
            if ($n >= $u && $n >= $a) $maxKey = 'notulensi';
            elseif ($a >= $u && $a >= $n) $maxKey = 'absensi';
            if ($maxKey==='undangan') $u += $diff;
            if ($maxKey==='notulensi') $n += $diff;
            if ($maxKey==='absensi') $a += $diff;
          }
        @endphp
        <span class="seg-undangan" style="width: {{ $u }}%"></span>
        <span class="seg-notulensi" style="width: {{ $n }}%"></span>
        <span class="seg-absensi"  style="width: {{ $a }}%"></span>
      </div>

      <div class="legend">
        <div><span class="dot dot-undangan"></span>Undangan: <b>{{ $metrics['by_type']['undangan'] }}</b> ({{ $pct['undangan'] }}%)</div>
        <div><span class="dot dot-notulensi"></span>Notulensi: <b>{{ $metrics['by_type']['notulensi'] }}</b> ({{ $pct['notulensi'] }}%)</div>
        <div><span class="dot dot-absensi"></span>Absensi: <b>{{ $metrics['by_type']['absensi'] }}</b> ({{ $pct['absensi'] }}%)</div>
      </div>
    </div>
  </div>

  {{-- ===== BUTUH TINDAKAN ANDA (tanggal+tempat disatukan) ===== --}}
  <div class="card mb-3">
    <div class="card-header"><b>Butuh Tindakan Anda (Siap Ditandatangani)</b></div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th style="width:8%">Jenis</th>
              <th>Rapat (Tanggal &amp; Tempat)</th>
              <th style="width:140px">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse($pendingOpen as $r)
              @php $cls = $r->doc_type==='undangan'?'doc-undangan':($r->doc_type==='notulensi'?'doc-notulensi':'doc-absensi'); @endphp
              <tr>
                <td class="text-center">
                  <span class="doc-badge {{ $cls }}">{{ ucfirst($r->doc_type) }}</span>
                </td>
                <td>
                  <div class="font-weight-bold">{{ $r->judul }}</div>
                  <div class="text-muted small mb-1">No: {{ $r->nomor_undangan ?? '-' }}</div>
                  <div class="small">
                    {{ \Carbon\Carbon::parse($r->tanggal)->translatedFormat('d F Y') }},
                    {{ $r->waktu_mulai }}
                    — <span class="text-muted">{{ $r->tempat }}</span>
                  </div>
                </td>
                <td class="text-right">
                  <a href="{{ route('approval.sign', $r->sign_token) }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-pen-nib mr-1"></i> Tanda Tangani
                  </a>
                </td>
              </tr>
            @empty
              <tr><td colspan="3" class="text-center text-muted p-3">Tidak ada yang perlu ditandatangani saat ini.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- ===== MENUNGGU TAHAP SEBELUMNYA (tanggal+tempat disatukan) ===== --}}
  <div class="card mb-3">
    <div class="card-header"><b>Menunggu Tahap Sebelumnya</b></div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th style="width:8%">Jenis</th>
              <th>Rapat (Tanggal &amp; Tempat)</th>
              <th style="width:160px">Status</th>
            </tr>
          </thead>
          <tbody>
            @forelse($pendingBlocked as $r)
              @php $cls = $r->doc_type==='undangan'?'doc-undangan':($r->doc_type==='notulensi'?'doc-notulensi':'doc-absensi'); @endphp
              <tr>
                <td class="text-center"><span class="doc-badge {{ $cls }}">{{ ucfirst($r->doc_type) }}</span></td>
                <td>
                  <div class="font-weight-bold">{{ $r->judul }}</div>
                  <div class="text-muted small mb-1">No: {{ $r->nomor_undangan ?? '-' }}</div>
                  <div class="small">
                    {{ \Carbon\Carbon::parse($r->tanggal)->translatedFormat('d F Y') }},
                    {{ $r->waktu_mulai }}
                    — <span class="text-muted">{{ $r->tempat }}</span>
                  </div>
                </td>
                <td><span class="chip yellow">Menunggu approver sebelumnya</span></td>
              </tr>
            @empty
              <tr><td colspan="3" class="text-center text-muted p-3">Tidak ada yang tertahan.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- ===== RIWAYAT APPROVED (tanggal+tempat disatukan, tanpa kolom step) ===== --}}
  <div class="card">
    <div class="card-header"><b>Riwayat Persetujuan (30 Hari Terakhir)</b></div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th style="width:8%">Jenis</th>
              <th>Rapat (Tanggal &amp; Tempat)</th>
              <th style="width:18%">Approved Pada</th>
            </tr>
          </thead>
          <tbody>
            @forelse($recentApproved as $r)
              @php $cls = $r->doc_type==='undangan'?'doc-undangan':($r->doc_type==='notulensi'?'doc-notulensi':'doc-absensi'); @endphp
              <tr>
                <td class="text-center"><span class="doc-badge {{ $cls }}">{{ ucfirst($r->doc_type) }}</span></td>
                <td>
                  <div class="font-weight-bold">{{ $r->judul }}</div>
                  <div class="text-muted small mb-1">No: {{ $r->nomor_undangan ?? '-' }}</div>
                  <div class="small">
                    {{ \Carbon\Carbon::parse($r->tanggal)->translatedFormat('d F Y') }}
                    — <span class="text-muted">{{ $r->tempat }}</span>
                  </div>
                </td>
                <td>{{ \Carbon\Carbon::parse($r->signed_at)->translatedFormat('d F Y H:i') }}</td>
              </tr>
            @empty
              <tr><td colspan="3" class="text-center text-muted p-3">Belum ada riwayat.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
