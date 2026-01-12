@extends('layouts.app')

@section('title','Dashboard Peserta')

@push('style')
<style>
  .metric-card{border:1px solid var(--border);border-radius:14px;background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.02));box-shadow:var(--shadow);color:var(--text)}
  .metric-card .card-body{display:flex;align-items:center;gap:14px;padding:16px 18px}
  .metric-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:linear-gradient(180deg,rgba(79,70,229,.25),rgba(14,165,233,.18));border:1px solid rgba(99,102,241,.25);font-size:20px;color:#fff}
  .metric-val{font-size:26px;font-weight:800;margin:0}
  .metric-sub{margin:0;color:var(--muted);font-size:13px;font-weight:600}
  .dash-card{border:1px solid var(--border);border-radius:14px;background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.02));box-shadow:var(--shadow);color:var(--text)}
  .dash-card .card-header{background:transparent;border-bottom:1px solid var(--border);font-weight:800;color:#fff}
  .list-item{padding:12px 14px;border-bottom:1px solid var(--border)}
  .list-item:last-child{border-bottom:none}
  .list-item .title{font-weight:700}
  .list-item small{color:var(--muted)}
  .table-mini{color:var(--text)}
  .table-mini thead th{background:rgba(79,70,229,.12);border-top:none;border-bottom:1px solid var(--border);text-transform:uppercase;font-size:.75rem;letter-spacing:.3px;text-align:center}
  .table-mini td{vertical-align:middle}
  @media (min-width: 992px){ .gutter-tight>[class^="col-"]{padding-left:10px;padding-right:10px} }

  /* badge chip agar konsisten */
  .badge-chip{margin-left:.5rem}
  .badge-chip.info{background:#162b44;border:1px solid #38bdf81a}

  /* penanda item tugas */
  .tg-item{border-left:4px solid transparent;border-radius:10px}
  .tg-pending{border-left-color:#9ca3af}

  /* ===== Mobile cards ===== */
  .m-list{padding:10px}
  .m-card{
    background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.02));
    border:1px solid rgba(226,232,240,.15);
    border-radius:12px;
    padding:12px 12px;
    margin-bottom:10px;
    color:var(--text);
  }
  .m-title{font-weight:800; line-height:1.25; font-size:1.02rem; color:#fff}
  .m-sub{font-size:.82rem; color:#9fb0cd}
  .m-meta{display:flex; flex-wrap:wrap; gap:8px; font-size:.8rem; color:#c7d2fe}
  .mchip{
    display:inline-flex; align-items:center; gap:.35rem;
    padding:.18rem .5rem; border-radius:999px; font-size:.72rem; font-weight:800;
    background:rgba(79,70,229,.25); border:1px solid rgba(79,70,229,.35); color:#fff;
  }
  .mchip.info{ background:rgba(14,165,233,.25); border-color:rgba(14,165,233,.35) }
  .mchip.warn{ background:rgba(245,158,11,.2); border-color:rgba(245,158,11,.35) }
  .mchip.ok{ background:rgba(34,197,94,.22); border-color:rgba(34,197,94,.35) }
  .btn-pill{
    display:inline-flex; align-items:center; gap:6px;
    border:1px solid rgba(255,255,255,.18);
    padding:.38rem .65rem; border-radius:10px; font-size:.86rem; color:#fff; text-decoration:none;
    background:rgba(255,255,255,.06);
  }
  .btn-pill.primary{ background:linear-gradient(180deg,var(--primary),var(--primary-700)); border-color:transparent; }
</style>
@endpush

@section('content')
<div class="container-fluid p-0">

  {{-- ====== METRICS ====== --}}
  <div class="row gutter-tight">
    <div class="col-md-3 mb-3">
      <div class="card metric-card">
        <div class="card-body">
          <div class="metric-icon"><i class="fas fa-inbox"></i></div>
          <div class="d-flex align-items-center w-100">
            <div>
              <p class="metric-val">{{ $stats['total_diundang'] ?? 0 }}</p>
              <p class="metric-sub mb-0">Total Diundang</p>
            </div>
            @if(($stats['upcoming_count'] ?? 0) > 0)
              <span class="badge-chip info ml-auto">
                {{ $stats['upcoming_count'] }}
                <span class="hint">Akan Datang</span>
              </span>
            @endif
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card metric-card">
        <div class="card-body">
          <div class="metric-icon"><i class="fas fa-user-check"></i></div>
          <div>
            <p class="metric-val">{{ $stats['hadir'] ?? 0 }}</p>
            <p class="metric-sub">Hadir</p>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card metric-card">
        <div class="card-body">
          <div class="metric-icon"><i class="fas fa-clipboard-check"></i></div>
          <div>
            <p class="metric-val">{{ $stats['tugas_selesai'] ?? 0 }}</p>
            <p class="metric-sub">Tugas Selesai</p>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card metric-card">
        <div class="card-body">
          <div class="metric-icon"><i class="fas fa-book-open"></i></div>
          <div class="d-flex align-items-center w-100">
            <div>
              <p class="metric-val">{{ $stats['notulensi_tersedia'] ?? 0 }}</p>
              <p class="metric-sub mb-0">Notulensi Tersedia</p>
            </div>
            <a href="{{ route('peserta.rapat') }}#notulensi"
               class="btn btn-sm btn-outline-light ml-auto">Lihat</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ====== (BARU) Daftar Tugas yang Harus Diselesaikan ====== --}}
  @php
    $tugasPending = ($tugas_saya ?? collect())->filter(fn($t)=> ($t->status ?? 'pending') === 'pending');
    $pendingCount = $tugasPending->count();
  @endphp
  <div class="row gutter-tight">
    <div class="col-12 mb-3">
      <div class="card dash-card">
        <div class="card-header d-flex align-items-center">
          <i class="fas fa-tasks mr-2"></i> Tugas yang Harus Diselesaikan
          <span class="badge-chip info ml-auto">{{ $pendingCount }} <span class="hint">Pending</span></span>
          <a href="{{ route('peserta.tugas.index') }}" class="btn btn-sm btn-outline-light ml-2">Lihat Semua</a>
        </div>
        <div class="card-body p-0">
          @if($pendingCount > 0)
            <ul class="list-group list-group-flush">
              @foreach($tugasPending->sortBy('tgl_penyelesaian')->take(5) as $t)
                @php
                  $due = $t->tgl_penyelesaian ? \Carbon\Carbon::parse($t->tgl_penyelesaian) : null;
                @endphp
                <li class="list-item tg-item tg-pending d-flex align-items-center">
                  <div class="flex-fill">
                    <div class="title">{{ $t->rapat_judul }}</div>
                    <small class="d-flex align-items-center" style="gap:.5rem">
                      {{ $due ? $due->isoFormat('D MMM Y') : 'â€”' }} â€¢
                      <span class="badge badge-secondary">Pending</span>
                    </small>
                  </div>
                  <a href="{{ route('peserta.rapat.show', $t->id_rapat) }}" class="btn btn-sm btn-outline-light ml-2">Detail</a>
                </li>
              @endforeach
            </ul>
          @else
            <div class="p-3 text-muted">Tidak ada tugas yang perlu diselesaikan.</div>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- ====== Rapat terdekat + Absensi perlu konfirmasi ====== --}}
  <div class="row gutter-tight">
    <div class="col-lg-6 mb-3">
      <div class="card dash-card h-100">
        <div class="card-header d-flex align-items-center">
          <i class="fas fa-bell mr-2"></i> Rapat Terdekat
          <a href="{{ route('peserta.rapat') }}" class="btn btn-sm btn-outline-light ml-auto">Semua</a>
        </div>
        <div class="card-body">
          @if(!empty($rapat_terdekat))
            <div class="list-item d-flex align-items-center">
              <div class="mr-3">
                <span class="btn btn-icon"><i class="far fa-calendar"></i></span>
              </div>
              <div class="flex-fill">
                <div class="title">{{ $rapat_terdekat->judul }}</div>
                <small>
                  {{ \Carbon\Carbon::parse($rapat_terdekat->tanggal)->isoFormat('dddd, D MMM Y') }}
                  â€¢ {{ \App\Helpers\TimeHelper::short($rapat_terdekat->waktu_mulai) }} WIT â€¢ {{ $rapat_terdekat->tempat }}
                </small>
              </div>
              <a href="{{ route('peserta.rapat.show', $rapat_terdekat->id) }}"
                 class="btn btn-sm btn-primary ml-3">
                <i class="fas fa-eye mr-1"></i> Detail Rapat
              </a>
            </div>
          @else
            <div class="text-muted">Tidak ada jadwal dekat.</div>
          @endif
        </div>
      </div>
    </div>

    <div class="col-lg-6 mb-3">
      <div class="card dash-card h-100">
        <div class="card-header d-flex align-items-center">
          <i class="fas fa-check-circle mr-2"></i> Absensi Perlu Konfirmasi
          @php $pendingCt = ($absensi_pending->count() ?? 0); @endphp
          @if($pendingCt>0)
            <span class="badge-chip warn ml-auto">
              {{ $pendingCt }} <span class="hint">Pending</span>
            </span>
          @endif
        </div>
        <div class="card-body p-0">
          @forelse($absensi_pending as $r)
            <div class="list-item d-flex align-items-center">
              <div class="mr-3 text-warning"><i class="fas fa-exclamation-circle"></i></div>
              <div class="flex-fill">
                <div class="title">{{ $r->judul }}</div>
                <small>
                  {{ \Carbon\Carbon::parse($r->tanggal)->isoFormat('ddd, D MMM Y') }}
                  â€¢ {{ \App\Helpers\TimeHelper::short($r->waktu_mulai) }} WIT â€¢ {{ $r->tempat }}
                </small>
              </div>
              <a href="{{ $r->token_qr ? route('absensi.scan', $r->token_qr) : route('peserta.absensi', $r->id) }}"
                 class="btn btn-sm btn-outline-light">Konfirmasi</a>
            </div>
          @empty
            <div class="p-3 text-muted">Tidak ada yang perlu dikonfirmasi.</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  {{-- ====== Rapat Akan Datang & Riwayat ====== --}}
  <div class="row gutter-tight">
    <div class="col-lg-6 mb-3">
      <div class="card dash-card h-100">
        <div class="card-header"><i class="far fa-calendar-alt mr-2"></i> Rapat Akan Datang (7 hari)</div>

        {{-- Desktop table --}}
        <div class="card-body p-0 d-none d-md-block">
          <table class="table table-mini table-hover mb-0">
            <thead>
              <tr>
                <th>Judul</th>
                <th>Tanggal</th>
                <th>Tempat</th>
                <th style="width:120px">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse($rapat_akan_datang as $r)
                <tr>
                  <td>{{ $r->judul }}</td>
                  <td class="text-center">{{ \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') }} <br>{{ \App\Helpers\TimeHelper::short($r->waktu_mulai) }}</td>
                  <td class="text-center">{{ $r->tempat }}</td>
                  <td class="text-center">
                    <a href="{{ route('peserta.rapat.show', $r->id) }}" class="btn btn-sm btn-primary">Detail</a>
                  </td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center text-muted p-3">Tidak ada jadwal dalam 7 hari.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

        {{-- Mobile cards --}}
        <div class="d-block d-md-none m-list">
          @forelse($rapat_akan_datang as $r)
            <div class="m-card">
              <div class="m-title">{{ $r->judul }}</div>
              <div class="m-meta mt-1">
                <span class="mchip info">{{ \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') }} â€¢ {{ \App\Helpers\TimeHelper::short($r->waktu_mulai) }}</span>
                <span class="mchip">{{ $r->tempat }}</span>
              </div>
              <div class="mt-2">
                <a href="{{ route('peserta.rapat.show', $r->id) }}" class="btn-pill primary">
                  <i class="fas fa-eye"></i> Detail
                </a>
              </div>
            </div>
          @empty
            <div class="text-muted text-center">Tidak ada jadwal dalam 7 hari.</div>
          @endforelse
        </div>

      </div>
    </div>

    <div class="col-lg-6 mb-3" id="notulensi">
      <div class="card dash-card h-100">
        <div class="card-header"><i class="fas fa-history mr-2"></i> Riwayat Rapat Terbaru</div>

        {{-- Desktop table --}}
        <div class="card-body p-0 d-none d-md-block">
          <table class="table table-mini table-hover mb-0">
            <thead>
              <tr>
                <th>Judul</th>
                <th class="text-center">Absensi</th>
                <th class="text-center">Notulensi</th>
              </tr>
            </thead>
            <tbody>
              @forelse($riwayat_rapat as $r)
                <tr>
                  <td>{{ $r->judul }} <br> {{ \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') }}</td>
                  <td class="text-center">
                    @if($r->absensi_status)
                      <span class="badge badge-success">{{ strtoupper($r->absensi_status) }}</span>
                    @else
                      <a href="{{ $r->token_qr ? route('absensi.scan', $r->token_qr) : route('peserta.absensi', $r->id) }}"
                         class="btn btn-sm btn-outline-light">Konfirmasi</a>
                    @endif
                  </td>
                  <td class="text-center">
                    @if((int)$r->ada_notulensi === 1)
                      <a href="{{ route('peserta.notulensi.show', $r->id) }}" class="btn btn-sm btn-info">Lihat</a>
                    @else
                      <span class="badge badge-secondary">Belum ada</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="4" class="text-center text-muted p-3">Belum ada riwayat.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

        {{-- Mobile cards --}}
        <div class="d-block d-md-none m-list">
          @forelse($riwayat_rapat as $r)
            <div class="m-card">
              <div class="m-title">{{ $r->judul }}</div>
              <div class="m-sub">{{ \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') }}</div>
              <div class="m-meta mt-1">
                @if($r->absensi_status)
                  <span class="mchip ok">Absensi: {{ strtoupper($r->absensi_status) }}</span>
                @else
                  <a href="{{ $r->token_qr ? route('absensi.scan', $r->token_qr) : route('peserta.absensi', $r->id) }}"
                     class="btn-pill">Konfirmasi Absensi</a>
                @endif

                @if((int)$r->ada_notulensi === 1)
                  <a href="{{ route('peserta.notulensi.show', $r->id) }}" class="btn-pill">
                    <i class="fas fa-file-alt"></i> Lihat Notulensi
                  </a>
                @else
                  <span class="mchip warn">Notulensi: Belum ada</span>
                @endif
              </div>
            </div>
          @empty
            <div class="text-muted text-center">Belum ada riwayat.</div>
          @endforelse
        </div>

      </div>
    </div>
  </div>
</div>

{{-- AJAX auto-save status tugas (biarkan default) --}}
<script>
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.btn-submit-fallback').forEach(b => b.style.display = 'none');
  document.querySelectorAll('.frm-update-status').forEach(frm => {
    const sel = frm.querySelector('.sel-status');
    if (!sel) return;
    sel.addEventListener('change', function(){
      const formData = new FormData(frm);
      fetch(frm.action, {
        method: 'POST',
        headers: { 'X-Requested-With':'XMLHttpRequest', 'X-CSRF-TOKEN': formData.get('_token') },
        body: new URLSearchParams([...formData, ['_method','PUT']])
      })
      .then(async r => {
        const ct = r.headers.get('content-type') || '';
        if (!ct.includes('application/json')) { frm.submit(); return; }
        const res = await r.json();
        if (!r.ok) throw res;
        console.log(res.message || 'Status tugas diperbarui');
      })
      .catch(() => frm.submit());
    });
  });
});
</script>
@endsection



