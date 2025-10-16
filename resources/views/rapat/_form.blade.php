{{-- resources/views/rapat/_form.blade.php --}} 

@php
  $isEdit = isset($rapat) && isset($rapat->id);

  // ====== Normalisasi peserta terpilih (Create/Edit) ======
  $selectedIds = []; $selectedLabels = [];

  if (is_array(old('peserta'))) {
      $selectedIds = array_values(array_unique(array_map('intval', old('peserta'))));
  } elseif (!empty($peserta_terpilih)) {
      $rows = is_object($peserta_terpilih) && method_exists($peserta_terpilih,'toArray')
            ? $peserta_terpilih->toArray() : $peserta_terpilih;
      foreach ($rows as $row) {
          $rid  = is_array($row) ? ($row['id'] ?? null) : ($row->id ?? null);
          $name = is_array($row) ? ($row['text'] ?? ($row['name'] ?? null)) : ($row->text ?? ($row->name ?? null));
          if ($rid===null) continue;
          $rid = (int)$rid; $selectedIds[]=$rid;
          if ($name) $selectedLabels[$rid]=$name;
      }
      $selectedIds = array_values(array_unique($selectedIds));
  }

  // label dari master (nama saja)
  $labelsFromMaster = collect($daftar_peserta)->keyBy('id')->map(fn($u)=>$u->name)->all();
  foreach ($selectedIds as $sid) {
    if (!isset($selectedLabels[$sid]) && isset($labelsFromMaster[$sid])) $selectedLabels[$sid]=$labelsFromMaster[$sid];
  }

  $selectedSet   = array_flip($selectedIds);
  $preselectJson = e(json_encode(array_map('strval', $selectedIds)));

  $masterIds = collect($daftar_peserta)->pluck('id')->map(fn($v)=>(int)$v)->all();
  $masterSet = array_flip($masterIds);
  $missingSelected = array_values(array_filter($selectedIds, fn($sid)=>!isset($masterSet[$sid])));

  $wrapperId = $pesertaWrapperId ?? 'peserta-wrapper';
@endphp

@push('styles')
@if(!$isEdit)
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
@endif
<style>
  .form-control:focus{
    background: rgba(255,255,255,.08);
    border-color: rgba(79,70,229,.55);
    box-shadow: 0 0 0 .15rem rgba(79,70,229,.25);
    color:#fff;
  }
  .input-group-text{ background:transparent !important; color:#fff !important; border:1px solid rgba(255,255,255,.2) }
  .toolbar{ display:flex; align-items:center; gap:.5rem; flex-wrap:wrap }
  .toolbar .btn{ padding:.35rem .6rem; border-radius:8px; font-weight:500 }

  @if(!$isEdit)
  /* ========== CREATE (Select2) ========== */
  .select2-container--default .select2-selection--multiple{
    display:flex; align-items:center; flex-wrap:wrap; gap:6px;
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.2);
    min-height: 40px; color:#fff;
    padding: 4px 6px; border-radius: 8px;
  }
  .select2-container--default .select2-selection--multiple .select2-selection__rendered{
    display:flex; align-items:center; flex-wrap:wrap; gap:6px;
    padding:0; margin:0; color:#fff;
  }
  .select2-container--default .select2-selection--multiple .select2-search--inline{ margin:0 }
  .select2-container--default .select2-selection--multiple .select2-search--inline .select2-search__field{
    height:26px; line-height:26px; margin:0; padding:0 2px;
    color:#fff; background:transparent; border:none; outline:0;
    transform: translateY(-2px);
  }
  .select2-container--default .select2-selection--multiple .select2-selection__choice{
    background: rgba(255,255,255,.12);
    border-color: rgba(255,255,255,.25);
    color:#fff; font-weight:500;
    border-radius: 20px; padding: 2px 8px; margin:0;
  }
  .select2-container--default .select2-selection--multiple .select2-selection__choice__remove{
    color:#fff !important; background:transparent !important;
    border:none !important; box-shadow:none !important;
    margin-right:6px; font-weight:700; line-height:1;
  }
  .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover{ color:#f87171 !important }
  .select2-dropdown{ background:#111827; color:#fff; border:1px solid rgba(255,255,255,.18) }
  .select2-search--dropdown .select2-search__field{ color:#fff; background:#1f2937; border:1px solid rgba(255,255,255,.18) }
  .select2-results__option{ color:#e5e7eb }
  .select2-results__option--highlighted{ background:#374151 !important; color:#fff }
  .select2-results__options{ max-height:320px !important; scrollbar-width:thin }
  .select2-results__options::-webkit-scrollbar{ height:8px; width:8px }
  .select2-results__options::-webkit-scrollbar-thumb{ background:#4b5563; border-radius:6px }
  @endif

  @if($isEdit)
  /* ========== EDIT (Checklist) ========== */
  .rapat-edit .checklist-head{ margin-bottom:.75rem; }
  .rapat-edit .checklist-title{ display:block; font-weight:600; margin-bottom:.25rem; }
  .rapat-edit .checklist-grid{
    display:grid; grid-template-columns: repeat(auto-fill, minmax(260px,1fr));
    gap:.6rem .9rem;
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.12);
    border-radius: .75rem; padding: .9rem 1rem;
  }
  .rapat-edit .check-item{
    display:flex; align-items:center; gap:.8rem;
    padding:.55rem .7rem; border-radius:.6rem;
    transition: background .15s ease, border-color .15s ease;
    border:1px solid transparent; cursor:pointer;
  }
  .rapat-edit .check-item input[type="checkbox"]{
    width:1.2rem; height:1.2rem;
    transform: translateY(1px) scale(1.08);
  }
  html body .rapat-edit .check-item .name{
    font-size:1.05rem !important; font-weight:700 !important; line-height:1.35 !important; color:#f9fafb !important;
  }
  .rapat-edit .check-item.is-checked{ background: rgba(99,102,241,.16); border-color: rgba(99,102,241,.38); }
  .rapat-edit .muted{ color:#9ca3af; font-style:italic }
  .rapat-edit .badge-missing{ display:inline-block; font-size:.75rem; padding:.15rem .4rem; border-radius:.4rem; background:#4b5563; color:#fff; margin-left:.35rem; }
  @endif
</style>
@endpush

{{-- ===== FIELD LAIN ===== --}}
<div class="form-group">
  <label>Nomor Undangan</label>
  <input type="text" name="nomor_undangan"
         value="{{ old('nomor_undangan', $rapat->nomor_undangan ?? '') }}"
         class="form-control" required>
</div>

<div class="form-group">
  <label>Judul</label>
  <input type="text" name="judul"
         value="{{ old('judul', $rapat->judul ?? '') }}"
         class="form-control" required>
</div>

<div class="form-group">
  <label>Deskripsi</label>
  <textarea name="deskripsi" class="form-control">{{ old('deskripsi', $rapat->deskripsi ?? '') }}</textarea>
</div>

<div class="form-row">
  <div class="form-group col-md-4">
    <label>Tanggal</label>
    <div class="input-group"><div class="input-group-prepend"></div>
      <input type="date" name="tanggal" value="{{ old('tanggal', $rapat->tanggal ?? '') }}" class="form-control" required>
    </div>
  </div>
  <div class="form-group col-md-4">
    <label>Waktu Mulai</label>
    <div class="input-group"><div class="input-group-prepend"></div>
      <input type="time" name="waktu_mulai" value="{{ old('waktu_mulai', $rapat->waktu_mulai ?? '') }}" class="form-control" required>
    </div>
  </div>
  <div class="form-group col-md-4">
    <label>Tempat</label>
    <div class="input-group"><div class="input-group-prepend"></div>
      <input type="text" name="tempat" value="{{ old('tempat', $rapat->tempat ?? '') }}" class="form-control" required>
    </div>
  </div>
</div>

<div class="form-group">
  <label>Kategori</label>
  <select name="id_kategori" class="form-control" required
          @isset($dropdownParentId) data-dropdown-parent="{{ $dropdownParentId }}" @endisset>
    <option value="">-- Pilih Kategori --</option>
    @foreach($daftar_kategori as $kategori)
      <option value="{{ $kategori->id }}" {{ old('id_kategori', $rapat->id_kategori ?? '') == $kategori->id ? 'selected' : '' }}>
        {{ $kategori->nama }}
      </option>
    @endforeach
  </select>
</div>

<div class="form-row">
  <div class="form-group col-md-6">
    <label>Approval 1 <span class="text-danger">*</span></label>
    <select name="approval1_user_id" class="form-control" required>
      <option value="">-- Pilih Approval 1 --</option>
      @foreach($approval1_list as $u)
        <option value="{{ $u->id }}" {{ old('approval1_user_id', $rapat->approval1_user_id ?? '') == $u->id ? 'selected' : '' }}>
          {{ $u->name }} {{ $u->tingkatan ? '(T'.$u->tingkatan.')' : '' }}
        </option>
      @endforeach
    </select>
  </div>
  <div class="form-group col-md-6">
    <label>Approval 2 (opsional)</label>
    <select name="approval2_user_id" class="form-control">
      <option value="">-- Tanpa Approval 2 --</option>
      @foreach($approval2_list as $u)
        <option value="{{ $u->id }}" {{ old('approval2_user_id', $rapat->approval2_user_id ?? '') == $u->id ? 'selected' : '' }}>
          {{ $u->name }} (T2)
        </option>
      @endforeach
    </select>
  </div>
</div>

{{-- ====== PESERTA ====== --}}
@if($isEdit)
  {{-- =========== EDIT: CHECKLIST (tampilkan NAMA SAJA) =========== --}}
  <section class="rapat-edit">
    <div class="d-flex align-items-center justify-content-between checklist-head">
      <div>
        <span class="checklist-title">Peserta (Checklist)</span>
        @if(count($missingSelected))
        <div class="alert alert-warning py-2 mb-2">
          <strong>Catatan:</strong> Ada peserta yang sebelumnya terundang tetapi tidak ada di daftar saat ini:
          @foreach($missingSelected as $sid)
            <span class="badge-missing">
              {{ $selectedLabels[$sid] ?? ('Pengguna #'.$sid) }} (ID: {{ $sid }})
            </span>
            <input type="hidden" name="peserta[]" value="{{ $sid }}">
          @endforeach
          <div class="mt-1 muted">Jika ingin menghapus, hapus dari undangan atau ubah role pengguna terkait.</div>
        </div>
        @endif
      </div>
      <div class="toolbar">
        <button type="button" class="btn btn-outline-light btn-sm" id="chkBtnAll">Pilih semua</button>
        <button type="button" class="btn btn-outline-success btn-sm" data-unit-pick="Kesekretariatan">Kesekretariatan</button>
        <button type="button" class="btn btn-outline-info btn-sm" data-unit-pick="Kepaniteraan">Kepaniteraan</button>
        <button type="button" class="btn btn-outline-danger btn-sm" id="chkBtnClear">Hapus semua</button>
      </div>
    </div>

    <div class="checklist-grid" id="checklistPeserta">
      @forelse($daftar_peserta as $p)
        @php
          $checked = isset($selectedSet[$p->id]);
          $unitTxt = trim($p->unit ?? '');   // tetap dipakai untuk filter tombol, tapi TIDAK ditampilkan
        @endphp
        <label class="check-item {{ $checked ? 'is-checked' : '' }}" @if($unitTxt!=='') data-unit="{{ $unitTxt }}" @endif>
          <input type="checkbox" name="peserta[]" value="{{ $p->id }}" {{ $checked ? 'checked' : '' }}>
          <span class="name">{{ $p->name }}</span> {{-- **NAMA SAJA** --}}
        </label>
      @empty
        <div class="muted">Belum ada pengguna non-admin yang dapat dipilih.</div>
      @endforelse
    </div>
  </section>

@else
  {{-- =========== CREATE: SELECT2 MULTIPLE (tampilkan NAMA SAJA) =========== --}}
  <div class="d-flex align-items-center justify-content-between mb-1">
    <label class="mb-0">Peserta</label>
    <div class="toolbar">
      <button type="button" class="btn btn-outline-light btn-sm" id="btnSelectAll">
        <i class="fas fa-check-double mr-1"></i> Pilih semua
      </button>
      <button type="button" class="btn btn-outline-success btn-sm" data-unit-pick-create="Kesekretariatan">Kesekretariatan</button>
      <button type="button" class="btn btn-outline-info btn-sm" data-unit-pick-create="Kepaniteraan">Kepaniteraan</button>
      <button type="button" class="btn btn-outline-light btn-sm" id="btnClearAll">
        <i class="fas fa-times mr-1"></i> Hapus semua
      </button>
    </div>
  </div>

  <div class="form-group" id="{{ $wrapperId }}">
    <select
      id="pesertaSelect"
      name="peserta[]"
      multiple
      style="width:100%;"
      data-placeholder="Pilih peserta rapat"
      data-preselect='{{ $preselectJson }}'
    >
      {{-- Jika ada selected yang tidak ada di master, tetap tampilkan --}}
      @foreach($missingSelected as $sid)
        <option value="{{ $sid }}" selected>
          {{ $selectedLabels[$sid] ?? 'Pengguna #'.$sid }}
        </option>
      @endforeach

      @foreach($daftar_peserta as $peserta)
        @php
          $isSel  = isset($selectedSet[$peserta->id]);
          $unitTx = trim($peserta->unit ?? '');   // disimpan untuk filter tombol
        @endphp
        <option value="{{ $peserta->id }}"
                @if($unitTx!=='') data-unit="{{ $unitTx }}" @endif
                {{ $isSel ? 'selected' : '' }}>
          {{ $labelsFromMaster[$peserta->id] ?? $peserta->name }} {{-- **NAMA SAJA** --}}
        </option>
      @endforeach
    </select>
    <small class="text-muted d-block mt-1">Tip: ketik untuk mencari nama peserta</small>
  </div>
@endif

@push('scripts')
@if(!$isEdit)
  {{-- CREATE: jQuery -> Select2 + Quick-Select per Unit --}}
  <script>
  (function(){
    function loadScript(src){return new Promise(function(res,rej){var s=document.createElement('script');s.src=src;s.onload=res;s.onerror=rej;document.head.appendChild(s);});}
    function ensureJQandS2(){
      let chain=Promise.resolve();
      if(!window.jQuery){chain=chain.then(()=>loadScript("https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"))}
      return chain.then(function(){
        if(!window.jQuery||!$.fn||!$.fn.select2){return loadScript("https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js")}
      });
    }
    function init(){
      var $sel=$('#pesertaSelect'); if(!$sel.length) return;
      if($sel.hasClass('select2-hidden-accessible')){try{$sel.select2('destroy')}catch(e){}}
      $sel.next('.select2-container').remove();
      var $w=$('#{{ $wrapperId }}');
      $sel.select2({
        width:'100%',
        placeholder:$sel.data('placeholder')||'Pilih peserta rapat',
        closeOnSelect:false,
        dropdownParent:$w.length?$w:$(document.body)
      });
      // Preselect
      try{
        var preset=JSON.parse($sel.attr('data-preselect')||'[]').map(String);
        $sel.val(preset).trigger('change');
      }catch(e){}

      // Pilih semua
      $('#btnSelectAll').off('click').on('click',function(){
        var vals=[]; $sel.find('option').each(function(){ this.selected=true; vals.push(this.value); });
        $sel.val(vals).trigger('change');
      });

      // Hapus semua
      $('#btnClearAll').off('click').on('click',function(){
        $sel.find('option').prop('selected',false);
        $sel.val([]).trigger('change');
      });

      // Quick-Select per Unit (merge pilihan)
      $('[data-unit-pick-create]').off('click').on('click', function(){
        var unit = ($(this).data('unit-pick-create')||'').toString().toLowerCase();
        var picked = $sel.find('option').filter(function(){
          var u = (this.getAttribute('data-unit')||'').toLowerCase();
          return u === unit;  // sesuaikan jika perlu contains()
        }).map(function(){ return this.value; }).get();

        var now = $sel.val() || [];
        var merged = Array.from(new Set([ ...now, ...picked ]));
        $sel.val(merged).trigger('change');
      });
    }
    ensureJQandS2().then(function(){
      if(document.readyState!=='loading') init(); else document.addEventListener('DOMContentLoaded',init);
      window.addEventListener('load',init);
    });
  })();
  </script>
@else
  {{-- EDIT: Checklist + Quick-Select per Unit --}}
  <script>
  (function(){
    function initChecklist(){
      var allBtn = document.getElementById('chkBtnAll');
      var clearB = document.getElementById('chkBtnClear');
      var grid   = document.getElementById('checklistPeserta');
      if(!grid) return;

      var boxes = Array.prototype.slice.call(grid.querySelectorAll('input[type="checkbox"][name="peserta[]"]'));

      function applyHighlight(b){
        var label = b.closest('label.check-item');
        if(!label) return;
        if(b.checked){ label.classList.add('is-checked'); }
        else{ label.classList.remove('is-checked'); }
      }

      function setAll(on){
        boxes.forEach(function(b){ b.checked=on; applyHighlight(b); });
      }

      if(allBtn) allBtn.addEventListener('click', function(){ setAll(true); });
      if(clearB) clearB.addEventListener('click', function(){ setAll(false); });

      // Tombol unit
      document.querySelectorAll('[data-unit-pick]').forEach(function(btn){
        btn.addEventListener('click', function(){
          var unit = (btn.getAttribute('data-unit-pick')||'').toLowerCase();
          boxes.forEach(function(b){
            var label = b.closest('label.check-item');
            var u = (label ? (label.getAttribute('data-unit')||'') : '').toLowerCase();
            if(u === unit){ b.checked = true; applyHighlight(b); }
          });
        });
      });

      // Highlight awal + listener per item
      boxes.forEach(function(b){
        applyHighlight(b);
        b.addEventListener('change', function(){ applyHighlight(b); });
      });
    }

    if(document.readyState!=='loading') initChecklist();
    else document.addEventListener('DOMContentLoaded', initChecklist);
    window.addEventListener('load', initChecklist);
  })();
  </script>
@endif
@endpush
