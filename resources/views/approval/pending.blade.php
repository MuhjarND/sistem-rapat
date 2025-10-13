{{-- resources/views/approval/pending.blade.php --}}
@extends('layouts.app')
@section('title','Approval Pending')

@section('style')
<style>
  .badge-chip{margin-left:0; display:inline-flex; align-items:center; gap:.35rem;
              background:rgba(255,255,255,.08); color:#fff; border-radius:999px;
              padding:.25rem .6rem; font-size:.78rem; font-weight:800; line-height:1;
              border:1px solid rgba(255,255,255,.2)}
  .badge-chip.fix{background:rgba(34,197,94,.18); border-color:rgba(34,197,94,.35)} /* Sudah diperbaiki */
  .doc-badge{font-weight:800; letter-spacing:.2px; border-radius:8px; padding:.12rem .4rem}
  .doc-undangan{background:#0ea5e9; color:#05293a}
  .doc-notulensi{background:#22c55e; color:#05341e}
  .doc-absensi{background:#f59e0b; color:#3a2705}
  .filter-bar .form-control{background:rgba(255,255,255,.06); border:1px solid var(--border); color:var(--text)}
  .filter-bar .form-control:focus{background:rgba(255,255,255,.08); border-color:rgba(79,70,229,.55); box-shadow:0 0 0 .15rem rgba(79,70,229,.25); color:var(--text)}
  .kpi-card{background:linear-gradient(180deg,rgba(255,255,255,.04),rgba(255,255,255,.02)); border:1px solid var(--border); border-radius:14px; box-shadow:var(--shadow); padding:12px; color:var(--text)}
  .kpi-card .value{font-size:1.4rem; font-weight:800}
  .table thead th{text-align:center}
  .meta-repair{display:flex;flex-wrap:wrap;gap:.5rem;color:var(--muted)}
  .meta-repair .sep{opacity:.6}
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
              $badgeCls  = $r->doc_type==='undangan' ? 'doc-undangan' : ($r->doc_type==='notulensi' ? 'doc-notulensi' : 'doc-absensi');
              $isBlocked = (bool)$r->blocked;
              $isResub   = !empty($r->resubmitted) && $r->doc_type==='notulensi';
              $revisedItems = (int)($r->revised_items ?? 0);
              $revisedDocs  = (int)($r->revised_docs  ?? 0);
            @endphp
            <tr data-type="{{ $r->doc_type }}"
                data-search="{{ \Illuminate\Support\Str::lower(($r->judul ?? '').' '.($r->tempat ?? '').' '.($r->nomor_undangan ?? '')) }}"
                data-status="{{ $isBlocked ? 'blocked' : 'open' }}"
                data-resubmitted="{{ $isResub ? 1 : 0 }}">
              <td class="text-center">
                <span class="doc-badge {{ $badgeCls }}">{{ ucfirst($r->doc_type) }}</span>
              </td>
              <td>
                <div class="font-weight-bold d-flex align-items-center flex-wrap" style="gap:.35rem">
                  <span>{{ $r->judul }}</span>
                  @if($isResub)
                    <span class="badge-chip fix"
                          data-toggle="tooltip"
                          title="Perbaikan terakhir {{ \Carbon\Carbon::parse($r->last_fix_at)->diffForHumans() }} • {{ $revisedItems }} butir / {{ $revisedDocs }} berkas">
                      Sudah diperbaiki
                    </span>
                  @endif
                </div>

                {{-- meta info perbaikan (ditampilkan teks kecil) --}}
                @if($isResub)
                  <div class="meta-repair mt-1">
                    @if(!empty($r->last_fix_at))
                      <span>Perbaikan {{ \Carbon\Carbon::parse($r->last_fix_at)->diffForHumans() }}</span>
                    @endif
                    <span class="sep">•</span>
                    <span>Perubahan: <b>{{ $revisedItems }}</b> butir / <b>{{ $revisedDocs }}</b> berkas</span>
                  </div>
                @endif

                <small class="text-muted d-block">No: {{ $r->nomor_undangan ?? '-' }}</small>
              </td>
              <td class="text-center">
                {{ \Carbon\Carbon::parse($r->tanggal)->translatedFormat('d F Y') }}<br>
                <small class="text-muted">{{ $r->waktu_mulai }}</small>
              </td>
              <td>{{ $r->tempat }}</td>
              <td class="text-center">Step {{ $r->order_index }}</td>
              <td class="text-right">
                @if(!$isBlocked)
                  @if(!empty($r->preview_url))
                    <a href="{{ $r->preview_url }}" target="_blank" class="btn btn-outline-light btn-sm mr-1">
                      <i class="fas fa-eye mr-1"></i> Preview
                    </a>
                  @endif
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

  @if(method_exists($rows, 'links'))
    <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap">
      <div class="text-muted mb-2">
        Menampilkan <b>{{ $rows->firstItem() ?? 0 }}</b>–<b>{{ $rows->lastItem() ?? 0 }}</b>
        dari <b>{{ $rows->total() }}</b> approval pending
      </div>
      <div class="mb-2">
        {{ $rows->onEachSide(1)->links() }}
      </div>
    </div>
  @endif

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
