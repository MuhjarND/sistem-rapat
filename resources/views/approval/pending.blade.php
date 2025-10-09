{{-- resources/views/approval/pending.blade.php --}}
@extends('layouts.app')
@section('title','Approval Pending')

@section('style')
<style>
  .badge-chip{margin-left:0; display:inline-flex; align-items:center; gap:.35rem;
              background:rgba(255,255,255,.08); color:#fff; border-radius:999px;
              padding:.25rem .6rem; font-size:.78rem; font-weight:800; line-height:1;
              border:1px solid rgba(255,255,255,.2)}
  .doc-badge{font-weight:800; letter-spacing:.2px; border-radius:8px; padding:.12rem .4rem}
  .doc-undangan{background:#0ea5e9; color:#05293a}
  .doc-notulensi{background:#22c55e; color:#05341e}
  .doc-absensi{background:#f59e0b; color:#3a2705}
  .filter-bar .form-control{background:rgba(255,255,255,.06); border:1px solid var(--border); color:var(--text)}
  .filter-bar .form-control:focus{background:rgba(255,255,255,.08); border-color:rgba(79,70,229,.55); box-shadow:0 0 0 .15rem rgba(79,70,229,.25); color:var(--text)}
  .kpi-card{background:linear-gradient(180deg,rgba(255,255,255,.04),rgba(255,255,255,.02)); border:1px solid var(--border); border-radius:14px; box-shadow:var(--shadow); padding:12px; color:var(--text)}
  .kpi-card .value{font-size:1.4rem; font-weight:800}
  .table thead th{text-align:center}
</style>
@endsection

@section('content')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Approval Pending</h3>
    <a href="{{ route('approval.dashboard') }}" class="btn btn-outline-light btn-sm">
      <i class="fas fa-home mr-1"></i> Kembali ke Dashboard
    </a>
  </div>

  {{-- RINGKASAN --}}
  @php
    $total        = $rows->count();
    $open         = $rows->where('blocked', false)->count();
    $blockedTotal = $rows->where('blocked', true)->count();

    $byType = [
      'undangan'  => $rows->where('doc_type','undangan')->count(),
      'notulensi' => $rows->where('doc_type','notulensi')->count(),
      'absensi'   => $rows->where('doc_type','absensi')->count(),
    ];
  @endphp
  <div class="row">
    <div class="col-md-3 mb-3">
      <div class="kpi-card">
        <div class="text-muted">Total Pending</div>
        <div class="value">{{ $total }}</div>
        <span class="badge-chip">Siap tanda tangan: {{ $open }}</span>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="kpi-card">
        <div class="text-muted">Menunggu Tahap Sebelumnya</div>
        <div class="value">{{ $blockedTotal }}</div>
        <span class="badge-chip">Tertahan</span>
      </div>
    </div>
    <div class="col-md-6 mb-3">
      <div class="kpi-card">
        <div class="text-muted mb-1">Per Jenis Dokumen</div>
        <div class="d-flex flex-wrap" style="gap:.4rem">
          <span class="doc-badge doc-undangan">Undangan: {{ $byType['undangan'] }}</span>
          <span class="doc-badge doc-notulensi">Notulensi: {{ $byType['notulensi'] }}</span>
          <span class="doc-badge doc-absensi">Absensi: {{ $byType['absensi'] }}</span>
        </div>
      </div>
    </div>
  </div>

  {{-- FILTER BAR (client-side) --}}
  <div class="card mb-3">
    <div class="card-body filter-bar">
      <div class="form-row align-items-end">
        <div class="form-group col-md-3">
          <label class="mb-1">Jenis Dokumen</label>
          <select id="fDocType" class="form-control">
            <option value="">Semua</option>
            <option value="undangan">Undangan</option>
            <option value="notulensi">Notulensi</option>
            <option value="absensi">Absensi</option>
          </select>
        </div>
        <div class="form-group col-md-4">
          <label class="mb-1">Pencarian (Judul/Tempat/No)</label>
          <input id="fSearch" type="text" class="form-control" placeholder="Ketik untuk mencari…">
        </div>
        <div class="form-group col-md-3">
          <label class="mb-1">Status</label>
          <select id="fStatus" class="form-control">
            <option value="">Semua</option>
            <option value="open">Siap Ditandatangani</option>
            <option value="blocked">Menunggu Tahap Sebelumnya</option>
          </select>
        </div>
        <div class="form-group col-md-2">
          <button id="btnReset" class="btn btn-outline-light btn-block"><i class="fas fa-undo mr-1"></i> Reset</button>
        </div>
      </div>
    </div>
  </div>

  {{-- TABEL --}}
  <div class="card">
    <div class="card-header"><b>Daftar Approval Pending</b></div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0" id="tblApproval">
          <thead>
            <tr>
              <th style="width:100px">Jenis</th>
              <th>Judul Rapat</th>
              <th style="width:16%">Tanggal</th>
              <th style="width:18%">Tempat</th>
              <th style="width:90px">Urutan</th>
              <th style="width:140px">Aksi</th>
            </tr>
          </thead>
          <tbody>
          @forelse($rows as $r)
            @php
              $badgeCls = $r->doc_type==='undangan' ? 'doc-undangan' : ($r->doc_type==='notulensi' ? 'doc-notulensi' : 'doc-absensi');
              $isBlocked = (bool)$r->blocked;
            @endphp
            <tr data-type="{{ $r->doc_type }}"
                data-search="{{ Str::lower(($r->judul ?? '').' '.($r->tempat ?? '').' '.($r->nomor_undangan ?? '')) }}"
                data-status="{{ $isBlocked ? 'blocked' : 'open' }}">
              <td class="text-center">
                <span class="doc-badge {{ $badgeCls }}">{{ ucfirst($r->doc_type) }}</span>
              </td>
              <td>
                <div class="font-weight-bold">{{ $r->judul }}</div>
                <small class="text-muted">No: {{ $r->nomor_undangan ?? '-' }}</small>
              </td>
              <td class="text-center">
                {{ \Carbon\Carbon::parse($r->tanggal)->translatedFormat('d F Y') }}<br>
                <small class="text-muted">{{ $r->waktu_mulai }}</small>
              </td>
              <td>{{ $r->tempat }}</td>
              <td class="text-center">Step {{ $r->order_index }}</td>
              <td class="text-right">
                @if(!$isBlocked)
                  <a href="{{ route('approval.sign', $r->sign_token) }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-pen-nib mr-1"></i> Tanda Tangani
                  </a>
                @else
                  <button type="button" class="btn btn-secondary btn-sm" disabled
                          data-toggle="tooltip" title="Menunggu approver tahap sebelumnya">
                    <i class="fas fa-lock mr-1"></i> Tertahan
                  </button>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-muted p-3">
                Belum ada approval pending.
              </td>
            </tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection

@section('script')
<script>
(function(){
  function applyFilter(){
    var t = ($('#fDocType').val() || '').toLowerCase();
    var q = ($('#fSearch').val() || '').toLowerCase().trim();
    var s = ($('#fStatus').val() || '').toLowerCase();

    $('#tblApproval tbody tr').each(function(){
      var $tr = $(this);
      var matchType = !t || ($tr.data('type') === t);
      var matchStatus = !s || ($tr.data('status') === s);
      var hay = ($tr.data('search') || '').toString();
      var matchSearch = !q || hay.indexOf(q) !== -1;

      $tr.toggle(matchType && matchStatus && matchSearch);
    });
  }

  $('#fDocType, #fStatus').on('change', applyFilter);
  $('#fSearch').on('input', applyFilter);
  $('#btnReset').on('click', function(e){
    e.preventDefault();
    $('#fDocType').val('');
    $('#fSearch').val('');
    $('#fStatus').val('');
    applyFilter();
  });

  $(function(){ $('[data-toggle="tooltip"]').tooltip(); });
})();
</script>
@endsection
