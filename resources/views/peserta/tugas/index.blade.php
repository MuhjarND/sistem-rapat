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
</style>
@endpush

@section('content')
<div class="container-fluid p-0">

  {{-- Ringkasan cepat --}}
  <div class="row mb-3">
    <div class="col-md-3">
      <div class="card dash-card"><div class="card-body py-2"><b>Total</b><div class="h4 mb-0">{{ $summary['total'] }}</div></div></div>
    </div>
    <div class="col-md-3">
      <div class="card dash-card"><div class="card-body py-2"><b>Pending</b><div class="h4 mb-0">{{ $summary['pending'] }}</div></div></div>
    </div>
    <div class="col-md-3">
      <div class="card dash-card"><div class="card-body py-2"><b>Selesai</b><div class="h4 mb-0">{{ $summary['done'] }}</div></div></div>
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

      {{-- Tabel --}}
      <div class="table-responsive">
        <table class="table table-mini table-hover mb-0">
          <thead>
            <tr>
              <th>Rapat</th>
              <th>Uraian / Rekomendasi</th>
              <th class="text-center">Tanggal Selesai Surat</th>
              <th class="text-center" style="width:200px">Status</th>
              <th class="text-center" style="width:110px">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse($tugas as $t)
              @php
                $tglSelesai = $t->tgl_penyelesaian_surat ?? $t->tgl_penyelesaian;
                $tglSelesaiCarbon = $tglSelesai ? \Carbon\Carbon::parse($tglSelesai) : null;
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

                    {{-- dropdown status bersih --}}
                    <select name="status" class="form-control form-control-sm sel-status" style="min-width:150px;">
                      <option value="pending" {{ $t->status === 'pending' ? 'selected' : '' }}>Pending</option>
                      <option value="done" {{ $t->status === 'done' ? 'selected' : '' }}>Selesai</option>
                    </select>

                    {{-- badge info --}}
                    <div class="status-badge-wrap">
                      <span class="badge {{ $t->status === 'done' ? 'badge-success' : 'badge-secondary' }} status-badge">
                        {{ $t->status === 'done' ? 'Selesai' : 'Pending' }}
                      </span>
                    </div>

                    <button type="submit" class="btn btn-sm btn-primary mt-1 btn-submit-fallback">Update</button>
                  </form>
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
    </div>

    <div class="card-footer">
      {{ $tugas->links() }}
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
      // ubah badge tampilan
      badge.textContent = sel.value === 'done' ? 'Selesai' : 'Pending';
      badge.classList.toggle('badge-success', sel.value === 'done');
      badge.classList.toggle('badge-secondary', sel.value !== 'done');

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
      .then(r => r.ok ? r.json() : Promise.reject())
      .then(res => console.log(res.message || 'Status tugas diperbarui'))
      .catch(() => frm.submit());
    });
  });
});
</script>
@endsection
