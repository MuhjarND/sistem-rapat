@extends('layouts.app')

@section('title','Tugas Notulensi Saya')

@push('style')
<style>
  .dash-card{
    border:1px solid var(--border);
    border-radius:14px;
    background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.02));
    box-shadow:var(--shadow);
    color:var(--text)
  }
  .dash-card .card-header{
    background:transparent;
    border-bottom:1px solid var(--border);
    font-weight:800;
    color:#fff
  }
  .table-mini thead th{
    background:rgba(79,70,229,.12);
    border-top:none;
    border-bottom:1px solid var(--border);
    text-transform:uppercase;
    font-size:.75rem;
    letter-spacing:.3px;
    text-align:center
  }

  .status-badge-wrap{ margin-top:6px; min-height:24px; }
  .status-badge{ font-weight:700; font-size:.8rem; padding:4px 10px; border-radius:12px; }

  /* ===== Mobile cards ===== */
  .m-wrap{padding:10px}
  .m-card{
    background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.02));
    border:1px solid rgba(226,232,240,.15);
    border-radius:12px;
    color:var(--text);
    padding:12px;
    margin-bottom:10px;
  }
  .m-title{font-weight:800; color:#fff; line-height:1.25}
  .m-sub{color:#9fb0cd; font-size:.85rem}
  .m-meta{display:flex; flex-wrap:wrap; gap:8px; margin-top:6px}
  .mchip{
    display:inline-flex; align-items:center; gap:.3rem;
    padding:.18rem .5rem; border-radius:999px; font-size:.72rem; font-weight:800;
    background:rgba(79,70,229,.22); border:1px solid rgba(79,70,229,.35); color:#fff;
  }
  .mchip.info{ background:rgba(14,165,233,.22); border-color:rgba(14,165,233,.35) }
  .mchip.warn{ background:rgba(245,158,11,.2); border-color:rgba(245,158,11,.35) }
  .mchip.ok{ background:rgba(34,197,94,.22); border-color:rgba(34,197,94,.35) }
  .m-actions{display:flex; flex-wrap:wrap; gap:8px; margin-top:10px}
  .btn-pill{
    display:inline-flex; align-items:center; gap:6px;
    border:1px solid rgba(255,255,255,.18);
    padding:.38rem .65rem; border-radius:10px; font-size:.86rem; color:#fff; text-decoration:none;
    background:rgba(255,255,255,.06);
  }
  .btn-pill.primary{ background:linear-gradient(180deg,var(--primary),var(--primary-700)); border-color:transparent; }
  .m-select{min-width:150px}

  /* Modal eviden */
  .modal-dark .modal-content{
    background:linear-gradient(180deg, rgba(15,23,42,.95), rgba(11,18,41,.95));
    color:var(--text);
    border:1px solid rgba(255,255,255,.12);
    border-radius:16px;
    box-shadow:0 20px 40px rgba(0,0,0,.5);
  }
  .modal-dark .modal-header,
  .modal-dark .modal-footer{
    border-color:rgba(255,255,255,.08);
  }
  .modal-dark .form-control{
    background:rgba(255,255,255,.08);
    border:1px solid rgba(255,255,255,.15);
    color:#fff;
  }
  .modal-dark .form-control:focus{
    border-color:rgba(99,102,241,.55);
    box-shadow:0 0 0 .15rem rgba(99,102,241,.3);
    color:#fff;
  }
  .modal-dark .btn-primary{
    background:linear-gradient(180deg,var(--primary),var(--primary-700));
    border:none;
  }
  .modal-dark .btn-secondary{
    background:rgba(255,255,255,.12);
    border:none;
    color:#fff;
  }
  .modal-dark .close span{
    color:#fff;
  }
</style>
@endpush

@section('content')
<div class="container-fluid p-0">

  {{-- Ringkasan cepat --}}
  <div class="row mb-3">
    <div class="col-md-3">
      <div class="card dash-card"><div class="card-body py-2"><b>Total</b><div class="h4 mb-0">{{ $summary['total'] ?? 0 }}</div></div></div>
    </div>
    <div class="col-md-3">
      <div class="card dash-card"><div class="card-body py-2"><b>Pending</b><div class="h4 mb-0">{{ $summary['pending'] ?? 0 }}</div></div></div>
    </div>
    <div class="col-md-3">
      <div class="card dash-card"><div class="card-body py-2"><b>Proses</b><div class="h4 mb-0">{{ $summary['proses'] ?? 0 }}</div></div></div>
    </div>
    <div class="col-md-3">
      <div class="card dash-card"><div class="card-body py-2"><b>Selesai</b><div class="h4 mb-0">{{ $summary['done'] ?? 0 }}</div></div></div>
    </div>
  </div>

  <div class="card dash-card">
    <div class="card-header d-flex align-items-center">
      <i class="fas fa-tasks mr-2"></i> Daftar Tugas Notulensi Saya
    </div>

    <div class="card-body">
      {{-- Filter --}}
      <form method="get" class="row g-2 mb-3">
        <div class="col-md-3">
          <input type="text" name="q" value="{{ $filter['q'] ?? '' }}" class="form-control" placeholder="Cari uraian/rekomendasi/judul rapat">
        </div>
        <div class="col-md-2">
          <select name="status" class="form-control">
            <option value="">Semua Status</option>
            <option value="pending" {{ ($filter['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="proses" {{ ($filter['status'] ?? '') === 'proses' ? 'selected' : '' }}>Proses</option>
            <option value="done" {{ ($filter['status'] ?? '') === 'done' ? 'selected' : '' }}>Selesai</option>
          </select>
        </div>
        <div class="col-md-2"><input type="date" name="from" value="{{ $filter['from'] ?? '' }}" class="form-control"></div>
        <div class="col-md-2"><input type="date" name="to" value="{{ $filter['to'] ?? '' }}" class="form-control"></div>
        <div class="col-md-3 d-flex">
          <input type="number" name="rapat" value="{{ $filter['rapat'] ?? '' }}" class="form-control mr-2" placeholder="ID Rapat (opsional)">
          <button class="btn btn-primary">Filter</button>
        </div>
      </form>

      {{-- ===== Desktop Table ===== --}}
      <div class="table-responsive d-none d-md-block">
        <table class="table table-mini table-hover mb-0">
          <thead>
            <tr>
              <th>Rapat</th>
              <th>Uraian / Rekomendasi</th>
              <th class="text-center">Tanggal Selesai Surat</th>
              <th class="text-center" style="width:210px">Status</th>
              <th class="text-center" style="width:230px">Pengumpulan Eviden</th>
              <th class="text-center" style="width:110px">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse($tugas as $t)
              @php
                $tglSelesai = $t->tgl_penyelesaian_surat ?? $t->tgl_penyelesaian;
                $tglSelesaiCarbon = $tglSelesai ? \Carbon\Carbon::parse($tglSelesai) : null;
                $statusBadgeMap = [
                  'pending'      => ['badge-secondary','Pending'],
                  'proses'       => ['badge-warning','Proses'],
                  'in_progress'  => ['badge-warning','Proses'],
                  'done'         => ['badge-success','Selesai'],
                ];
                $statusBadge = $statusBadgeMap[$t->status] ?? ['badge-secondary', ucfirst($t->status)];
              @endphp
              <tr>
                <td>
                  <div class="font-weight-bold">{{ $t->rapat_judul }}</div>
                  <small class="text-muted">
                    {{ \Carbon\Carbon::parse($t->rapat_tanggal)->isoFormat('D MMM Y') }}
                    • {{ $t->rapat_waktu_mulai }} WIT • {{ $t->rapat_tempat }}
                  </small>
                </td>
                <td>
                  {!! $t->hasil_pembahasan ?? '-' !!}
                  @if(!empty($t->rekomendasi))
                    <div class="mt-1"><span class="badge badge-info">Rekomendasi</span> {!! $t->rekomendasi !!}</div>
                  @endif
                </td>
                <td class="text-center">
                  {{ $tglSelesaiCarbon ? $tglSelesaiCarbon->isoFormat('D MMM Y') : '—' }}
                </td>
                <td class="text-center">
                  <form class="frm-update-status d-inline" method="POST" action="{{ route('peserta.tugas.update', $t->id) }}">
                    @csrf @method('PUT')
                    <select name="status" class="form-control form-control-sm sel-status" style="min-width:150px;">
                      <option value="pending" {{ $t->status === 'pending' ? 'selected' : '' }}>Pending</option>
                      <option value="proses" {{ $t->status === 'proses' ? 'selected' : '' }}>Proses</option>
                      <option value="done" {{ $t->status === 'done' ? 'selected' : '' }}>Selesai</option>
                    </select>
                    <div class="status-badge-wrap">
                      <span class="badge {{ $statusBadge[0] }} status-badge">
                        {{ $statusBadge[1] }}
                      </span>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary mt-1 btn-submit-fallback">Update</button>
                  </form>
                </td>
                <td>
                  <div class="mb-2 text-sm">
                    @if($t->eviden_path)
                      <div><a href="{{ asset($t->eviden_path) }}" target="_blank"><i class="fas fa-image mr-1"></i>Lihat Gambar</a></div>
                    @endif
                    @if($t->eviden_link)
                      <div><a href="{{ $t->eviden_link }}" target="_blank"><i class="fas fa-link mr-1"></i>Link Eviden</a></div>
                    @endif
                    @if(!$t->eviden_path && !$t->eviden_link)
                      <small class="text-muted">Belum ada eviden.</small>
                    @endif
                  </div>
                  <button type="button"
                          class="btn btn-sm btn-outline-primary btn-modal-eviden"
                          data-id="{{ $t->id }}"
                          data-action="{{ route('peserta.tugas.eviden', $t->id) }}"
                          data-rapat="{{ $t->rapat_judul }}"
                          data-existing-file="{{ $t->eviden_path ? asset($t->eviden_path) : '' }}"
                          data-existing-link="{{ $t->eviden_link ?? '' }}">
                    Kumpulkan Eviden
                  </button>
                </td>
                <td class="text-center">
                  <a href="{{ route('peserta.rapat.show', $t->id_rapat) }}" class="btn btn-sm btn-outline-light">Detail</a>
                </td>
              </tr>
            @empty
              <tr><td colspan="5" class="text-center text-muted p-3">Belum ada tugas.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- ===== Mobile Cards ===== --}}
      <div class="d-block d-md-none m-wrap">
        @forelse($tugas as $t)
          @php
            $tglSelesai = $t->tgl_penyelesaian_surat ?? $t->tgl_penyelesaian;
            $tglSelesaiCarbon = $tglSelesai ? \Carbon\Carbon::parse($tglSelesai) : null;
            $statusBadgeMap = [
              'pending'      => ['badge-secondary','Pending'],
              'proses'       => ['badge-warning','Proses'],
              'in_progress'  => ['badge-warning','Proses'],
              'done'         => ['badge-success','Selesai'],
            ];
            $statusBadge = $statusBadgeMap[$t->status] ?? ['badge-secondary', ucfirst($t->status)];
          @endphp
          <div class="m-card">
            <div class="m-title">{{ $t->rapat_judul }}</div>
            <div class="m-sub">
              {{ \Carbon\Carbon::parse($t->rapat_tanggal)->isoFormat('ddd, D MMM Y') }}
              • {{ $t->rapat_waktu_mulai }} WIT • {{ $t->rapat_tempat }}
            </div>

            <div class="mt-2">
              <div class="font-weight-bold mb-1">Uraian</div>
              <div class="text-light">{!! $t->hasil_pembahasan ?? '—' !!}</div>
              @if(!empty($t->rekomendasi))
                <div class="mt-1"><span class="mchip info">Rekomendasi</span></div>
              @endif
            </div>

            <div class="m-meta">
              <span class="mchip">Tenggat:
                {{ $tglSelesaiCarbon ? $tglSelesaiCarbon->isoFormat('D MMM Y') : '—' }}
              </span>
            </div>

            <div class="mt-3">
              <div class="font-weight-bold mb-1">Eviden</div>
              <div class="text-light small">
                @if($t->eviden_path)
                  <div><a href="{{ asset($t->eviden_path) }}" target="_blank" class="text-light"><i class="fas fa-image mr-1"></i>Lihat Gambar</a></div>
                @endif
                @if($t->eviden_link)
                  <div><a href="{{ $t->eviden_link }}" target="_blank" class="text-light"><i class="fas fa-link mr-1"></i>Link Eviden</a></div>
                @endif
                @if(!$t->eviden_path && !$t->eviden_link)
                  <div class="text-muted">Belum ada eviden.</div>
                @endif
              </div>
              <button type="button"
                      class="btn-pill primary mt-2 btn-modal-eviden"
                      data-id="{{ $t->id }}"
                      data-action="{{ route('peserta.tugas.eviden', $t->id) }}"
                      data-rapat="{{ $t->rapat_judul }}"
                      data-existing-file="{{ $t->eviden_path ? asset($t->eviden_path) : '' }}"
                      data-existing-link="{{ $t->eviden_link ?? '' }}">
                <i class="fas fa-upload"></i> Kumpulkan Eviden
              </button>
            </div>

            <div class="m-actions">
              {{-- Status (AJAX) --}}
              <form class="frm-update-status d-inline-flex align-items-center" method="POST" action="{{ route('peserta.tugas.update', $t->id) }}">
                @csrf @method('PUT')
                <select name="status" class="form-control form-control-sm sel-status m-select">
                  <option value="pending" {{ $t->status === 'pending' ? 'selected' : '' }}>Pending</option>
                  <option value="proses" {{ $t->status === 'proses' ? 'selected' : '' }}>Proses</option>
                  <option value="done" {{ $t->status === 'done' ? 'selected' : '' }}>Selesai</option>
                </select>
                <button type="submit" class="btn btn-sm btn-primary ml-2 btn-submit-fallback">Update</button>
                <span class="ml-2 badge {{ $statusBadge[0] }} status-badge">
                  {{ $statusBadge[1] }}
                </span>
              </form>

              <a href="{{ route('peserta.notulensi.show', $t->id_rapat) }}" class="btn-pill primary">
                <i class="fas fa-eye"></i> Detail
              </a>
            </div>
          </div>
        @empty
          <div class="text-center text-muted">Belum ada tugas.</div>
        @endforelse
      </div>
    </div>

    <div class="card-footer">
      {{ $tugas->links() }}
    </div>
  </div>
</div>

{{-- Modal Eviden --}}
<div class="modal fade modal-dark" id="modalEviden" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEvidenLabel">Pengumpulan Eviden</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form method="POST" enctype="multipart/form-data" id="formEviden">
        @csrf
        <div class="modal-body">
          <div class="mb-2">
            <small class="text-muted">Tugas: <span id="modalEvidenRapat">-</span></small>
          </div>
          <div class="mb-3" id="modalEvidenExisting">
            <small class="text-muted">Belum ada eviden.</small>
          </div>
          <div class="form-group">
            <label>Unggah Gambar (maks 2MB)</label>
            <input type="file" name="eviden_file" accept="image/*" class="form-control">
          </div>
          <div class="form-group">
            <label>Link Eviden</label>
            <input type="url" name="eviden_link" class="form-control" placeholder="https://contoh.com/eviden">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
          <button type="submit" class="btn btn-primary">Kirim Eviden</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- AJAX auto-save status + badge dinamis --}}
<script>
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.btn-submit-fallback').forEach(b => b.style.display = 'none');

  document.querySelectorAll('.frm-update-status').forEach(function(frm){
    const sel = frm.querySelector('.sel-status');
    const badge = frm.querySelector('.status-badge');

    if (!sel || !badge) return;

    sel.addEventListener('change', function(){
      const meta = {
        pending: { text: 'Pending', class: 'badge-secondary' },
        proses:  { text: 'Proses',  class: 'badge-warning' },
        in_progress: { text: 'Proses', class: 'badge-warning' },
        done:    { text: 'Selesai', class: 'badge-success' },
      }[sel.value] || { text: sel.value, class: 'badge-secondary' };

      // ubah badge tampilan
      badge.textContent = meta.text;
      badge.classList.remove('badge-secondary','badge-warning','badge-success');
      badge.classList.add(meta.class);

      // kirim AJAX
      const formData = new FormData(frm);
      fetch(frm.action, {
        method: 'POST',
        headers: {
          'X-Requested-With':'XMLHttpRequest',
          'X-CSRF-TOKEN': formData.get('_token')
        },
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

  const evidenModalEl = document.getElementById('modalEviden');
  const evidenForm = document.getElementById('formEviden');
  const evidenRapat = document.getElementById('modalEvidenRapat');
  const evidenExisting = document.getElementById('modalEvidenExisting');
  let evidenModalInstance = null;
  if (window.bootstrap && evidenModalEl) {
    evidenModalInstance = new bootstrap.Modal(evidenModalEl);
  }

  document.querySelectorAll('.btn-modal-eviden').forEach(function(btn){
    btn.addEventListener('click', function(){
      const action = this.dataset.action;
      const rapat = this.dataset.rapat || '-';
      const file = this.dataset.existingFile;
      const link = this.dataset.existingLink;

      evidenForm.reset();
      evidenForm.action = action;
      evidenRapat.textContent = rapat;

      if ((file && file.length) || (link && link.length)) {
        let html = '';
        if (file) {
          html += `<div><a href="${file}" target="_blank"><i class="fas fa-image mr-1"></i> Lihat Gambar</a></div>`;
        }
        if (link) {
          html += `<div><a href="${link}" target="_blank"><i class="fas fa-link mr-1"></i> Link Eviden</a></div>`;
        }
        evidenExisting.innerHTML = html;
      } else {
        evidenExisting.innerHTML = '<small class="text-muted">Belum ada eviden.</small>';
      }

      if (evidenModalInstance) {
        evidenModalInstance.show();
      } else {
        $(evidenModalEl).modal('show');
      }
    });
  });
});
</script>
@endsection
