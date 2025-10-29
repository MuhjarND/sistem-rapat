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
  $labelsFromMaster = collect($daftar_peserta)->keyBy('id')->map(function($u){ return $u->name; })->all();
  foreach ($selectedIds as $sid) {
    if (!isset($selectedLabels[$sid]) && isset($labelsFromMaster[$sid])) $selectedLabels[$sid]=$labelsFromMaster[$sid];
  }

  $selectedSet   = array_flip($selectedIds);
  $preselectJson = e(json_encode(array_map('strval', $selectedIds)));

  $masterIds = collect($daftar_peserta)->pluck('id')->map(function($v){ return (int)$v; })->all();
  $masterSet = array_flip($masterIds);
  $missingSelected = array_values(array_filter($selectedIds, function($sid) use ($masterSet){ return !isset($masterSet[$sid]); }));

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
  .toolbar .btn{ padding:.35rem .6rem; border-radius:8px; font-weight:600 }
  .toolbar-group{ display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; margin:.25rem 0 }
  .toolbar-label{ font-size:.95rem; font-weight:800; letter-spacing:.2px; color:#e2e8f0; margin-right:.25rem; }

  /* ===== CHIP TANPA .btn BOOTSTRAP (kontras tinggi) ===== */
  .chip{
    display:inline-flex; align-items:center; gap:.35rem;
    border-radius:999px; padding:.38rem .86rem; font-size:.9rem; line-height:1;
    text-decoration:none; cursor:pointer; user-select:none;
    border:1px solid transparent; box-shadow:0 1px 0 rgba(0,0,0,.12);
    transition:filter .12s, transform .04s, box-shadow .12s, background .12s, border-color .12s, color .12s;
  }
  .chip:focus{ outline:0; box-shadow:0 0 0 .16rem rgba(148,163,184,.35) }
  .chip:active{ transform: translateY(1px); }

  /* base */
  .chip-base{ color:#0f172a !important; background:#e2e8f0 !important; border-color:#cbd5e1 !important; }
  .chip-base:hover{ filter:brightness(1.05); }

  /* Bidang (teal) */
  .chip-bidang{ color:#022c22 !important; background:#5eead4 !important; border-color:#2dd4bf !important; }
  .chip-bidang.active{ color:#f70000 !important; background:linear-gradient(180deg,#14b8a6,#0d9488) !important; border-color:#99f6e4 !important; box-shadow:0 0 0 .16rem rgba(20,184,166,.25) !important; }

  /* Unit (indigo) */
  .chip-unit{ color:#111827 !important; background:#c7d2fe !important; border-color:#a5b4fc !important; }
  .chip-unit.active{ color:#fff !important; background:linear-gradient(180deg,#6366f1,#4f46e5) !important; border-color:#c7d2fe !important; box-shadow:0 0 0 .16rem rgba(99,102,241,.28) !important; }

  /* Tombol outline utama biar kontras */
  .toolbar .btn.btn-outline-light{ color:#e5edff !important; border-color: rgba(203,213,225,.55) !important; }
  .toolbar .btn.btn-outline-light:hover{ background: rgba(203,213,225,.14) !important; }
  .toolbar .btn.btn-outline-danger{ color:#ffe4e6 !important; border-color: rgba(248,113,113,.6) !important; }
  .toolbar .btn.btn-outline-danger:hover{ background: rgba(248,113,113,.14) !important; }

  @if(!$isEdit)
  /* ========== CREATE (Select2) ========== */
  .select2-container--default .select2-selection--multiple{
    display:flex; align-items:center; flex-wrap:wrap; gap:6px;
    background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.2);
    min-height: 40px; color:#fff; padding: 4px 6px; border-radius: 8px;
  }
  .select2-container--default .select2-selection--multiple .select2-selection__rendered{
    display:flex; align-items:center; flex-wrap:wrap; gap:6px; padding:0; margin:0; color:#fff;
  }
  .select2-dropdown{ background:#111827; color:#fff; border:1px solid rgba(255,255,255,.18) }
  .select2-results__option{ color:#e5e7eb }
  .select2-results__option--highlighted{ background:#374151 !important; color:#fff }
  @endif

  @if($isEdit)
  /* ========== EDIT (Checklist) ========== */
  .rapat-edit .checklist-head{ margin-bottom:.75rem; }
  .rapat-edit .checklist-title{ display:block; font-weight:700; margin-bottom:.25rem; }
  .rapat-edit .checklist-grid{
    display:grid; grid-template-columns: repeat(auto-fill, minmax(260px,1fr));
    gap:.6rem .9rem; background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.12); border-radius: .75rem; padding: .9rem 1rem;
  }
  .rapat-edit .check-item{ display:flex; align-items:center; gap:.8rem; padding:.55rem .7rem; border-radius:.6rem; transition: background .15s ease, border-color .15s ease; border:1px solid transparent; cursor:pointer; }
  .rapat-edit .check-item input[type="checkbox"]{ width:1.2rem; height:1.2rem; transform: translateY(1px) scale(1.08); }
  .rapat-edit .check-item .name{ font-size:1.05rem; font-weight:800; color:#f9fafb; }
  .rapat-edit .check-item.is-checked{ background: rgba(99,102,241,.22); border-color: rgba(165,180,252,.65); }
  .rapat-edit .muted{ color:#9ca3af; font-style:italic }
  .rapat-edit .badge-missing{ display:inline-block; font-size:.75rem; padding:.15rem .4rem; border-radius:.4rem; background:#4b5563; color:#fff; margin-left:.35rem; }
  @endif
</style>
@endpush

{{-- ===== FIELD LAIN ===== --}}
<div class="form-group">
  <label>Nomor Undangan</label>
  <input type="text" name="nomor_undangan" value="{{ old('nomor_undangan', $rapat->nomor_undangan ?? '') }}" class="form-control" required>
</div>

<div class="form-group">
  <label>Judul</label>
  <input type="text" name="judul" value="{{ old('judul', $rapat->judul ?? '') }}" class="form-control" required>
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
      <option value="{{ $kategori->id }}" {{ old('id_kategori', $rapat->id_kategori ?? '') == $kategori->id ? 'selected' : '' }}>{{ $kategori->nama }}</option>
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
  {{-- =========== EDIT: CHECKLIST =========== --}}
  <section class="rapat-edit">
    <div class="d-flex align-items-center justify-content-between checklist-head">
      <div>
        <span class="checklist-title">Peserta (Checklist)</span>
        @if(count($missingSelected))
        <div class="alert alert-warning py-2 mb-2">
          <strong>Catatan:</strong> Ada peserta yang sebelumnya terundang tetapi tidak ada di daftar saat ini:
          @foreach($missingSelected as $sid)
            <span class="badge-missing">{{ $selectedLabels[$sid] ?? ('Pengguna #'.$sid) }} (ID: {{ $sid }})</span>
            <input type="hidden" name="peserta[]" value="{{ $sid }}">
          @endforeach
          <div class="mt-1 muted">Jika ingin menghapus, hapus dari undangan atau ubah role pengguna terkait.</div>
        </div>
        @endif
      </div>

      <div class="toolbar">
        <button type="button" class="btn btn-outline-light btn-sm" id="chkBtnAll">Pilih semua</button>
        <button type="button" class="btn btn-outline-danger btn-sm" id="chkBtnClear">Hapus semua</button>
      </div>
    </div>

    {{-- Bidang & Unit --}}
    <div class="mb-2">
      <div class="toolbar-group mb-1">
        <span class="toolbar-label">Bidang:</span>
        @forelse(($daftar_bidang ?? []) as $b)
          <button type="button" class="chip chip-bidang" data-bidang-pick="{{ e($b) }}">{{ e($b) }}</button>
        @empty
          <span class="text-muted small">Tidak ada data bidang.</span>
        @endforelse
      </div>
      <div class="toolbar-group">
        <span class="toolbar-label">Unit:</span>
        @forelse(($daftar_unit ?? []) as $u)
          <button type="button" class="chip chip-unit" data-unit-pick="{{ e($u) }}">{{ e($u) }}</button>
        @empty
          <span class="text-muted small">Tidak ada data unit.</span>
        @endforelse
      </div>
    </div>

    <div class="checklist-grid" id="checklistPeserta">
      @forelse($daftar_peserta as $p)
        @php
          $checked = isset($selectedSet[$p->id]);
          $unitTxt = trim($p->unit ?? '');
          $bidTxt  = trim($p->bidang ?? '');
        @endphp
        <label class="check-item {{ $checked ? 'is-checked' : '' }}"
               @if($unitTxt!=='')  data-unit="{{ e($unitTxt) }}"   @endif
               @if($bidTxt!=='')   data-bidang="{{ e($bidTxt) }}"  @endif>
          <input type="checkbox" name="peserta[]" value="{{ $p->id }}" {{ $checked ? 'checked' : '' }}>
          <span class="name">{{ $p->name }}</span>
        </label>
      @empty
        <div class="muted">Belum ada pengguna non-admin yang dapat dipilih.</div>
      @endforelse
    </div>
  </section>

@else
  {{-- =========== CREATE =========== --}}
  <div class="d-flex align-items-center justify-content-between mb-2">
    <label class="mb-0">Peserta</label>
    <div class="toolbar">
      <button type="button" class="btn btn-outline-light btn-sm" id="btnSelectAll"><i class="fas fa-check-double mr-1"></i> Pilih semua</button>
      <button type="button" class="btn btn-outline-light btn-sm" id="btnClearAll"><i class="fas fa-times mr-1"></i> Hapus semua</button>
    </div>
  </div>

  <div class="mb-2">
    <div class="toolbar-group mb-1">
      <span class="toolbar-label">Bidang:</span>
      @forelse(($daftar_bidang ?? []) as $b)
        <button type="button" class="chip chip-bidang" data-bidang-pick-create="{{ e($b) }}">{{ e($b) }}</button>
      @empty
        <span class="text-muted small">Tidak ada data bidang.</span>
      @endforelse
    </div>
    <div class="toolbar-group">
      <span class="toolbar-label">Unit:</span>
      @forelse(($daftar_unit ?? []) as $u)
        <button type="button" class="chip chip-unit" data-unit-pick-create="{{ e($u) }}">{{ e($u) }}</button>
      @empty
        <span class="text-muted small">Tidak ada data unit.</span>
      @endforelse
    </div>
  </div>

  <div class="form-group" id="{{ $wrapperId }}">
    <select id="pesertaSelect" name="peserta[]" multiple style="width:100%;" data-placeholder="Pilih peserta rapat" data-preselect='{{ $preselectJson }}'>
      @foreach($missingSelected as $sid)
        <option value="{{ $sid }}" selected>{{ $selectedLabels[$sid] ?? 'Pengguna #'.$sid }}</option>
      @endforeach
      @foreach($daftar_peserta as $peserta)
        @php
          $isSel  = isset($selectedSet[$peserta->id]);
          $unitTx = trim($peserta->unit ?? '');
          $bidTx  = trim($peserta->bidang ?? '');
        @endphp
        <option value="{{ $peserta->id }}"
                @if($unitTx!=='') data-unit="{{ e($unitTx) }}" @endif
                @if($bidTx!=='')  data-bidang="{{ e($bidTx) }}" @endif
                {{ $isSel ? 'selected' : '' }}>
          {{ $labelsFromMaster[$peserta->id] ?? $peserta->name }}
        </option>
      @endforeach
    </select>
    <small class="text-muted d-block mt-1">Tip: ketik untuk mencari nama peserta</small>
  </div>
@endif

@push('scripts')
@if(!$isEdit)
  <script>
  (function(){
    function loadScript(src){
      return new Promise(function(res,rej){
        var s=document.createElement('script'); s.src=src; s.onload=res; s.onerror=rej; document.head.appendChild(s);
      });
    }
    function ensureJQandS2(){
      var chain=Promise.resolve();
      if(!window.jQuery){
        chain = chain.then(function(){ return loadScript("https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"); });
      }
      return chain.then(function(){
        if(!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2){
          return loadScript("https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js");
        }
      });
    }
    function normalize(str){ return (str||'').toString().trim().toLowerCase(); }

    function init(){
      var $sel = jQuery('#pesertaSelect'); if(!$sel.length) return;

      var wrapperId = @json($wrapperId ?? '');
      var $w = wrapperId ? jQuery('#'+wrapperId) : jQuery(document.body);

      if($sel.hasClass('select2-hidden-accessible')){ try{ $sel.select2('destroy'); }catch(e){} }
      $sel.next('.select2-container').remove();

      $sel.select2({ width:'100%', placeholder:$sel.data('placeholder')||'Pilih peserta rapat', closeOnSelect:false, dropdownParent:$w.length?$w:jQuery(document.body) });

      try{
        var preset = JSON.parse($sel.attr('data-preselect')||'[]').map(String);
        $sel.val(preset).trigger('change');
      }catch(e){}

      jQuery('#btnSelectAll').off('click').on('click',function(){
        var vals=[]; $sel.find('option').each(function(){ this.selected=true; vals.push(this.value); });
        $sel.val(vals).trigger('change');
      });
      jQuery('#btnClearAll').off('click').on('click',function(){
        $sel.find('option').prop('selected',false); $sel.val([]).trigger('change');
      });

      // Quick-Select Unit
      jQuery('[data-unit-pick-create]').off('click').on('click', function(){
        jQuery('[data-unit-pick-create]').removeClass('active'); jQuery(this).addClass('active');
        var unit = normalize(jQuery(this).data('unit-pick-create'));
        var picked = $sel.find('option').filter(function(){ return normalize(this.getAttribute('data-unit')) === unit; })
                      .map(function(){ return this.value; }).get();
        var now = $sel.val() || [];
        $sel.val(Array.from(new Set(now.concat(picked)))).trigger('change');
      });

      // Quick-Select Bidang
      jQuery('[data-bidang-pick-create]').off('click').on('click', function(){
        jQuery('[data-bidang-pick-create]').removeClass('active'); jQuery(this).addClass('active');
        var bidang = normalize(jQuery(this).data('bidang-pick-create'));
        var picked = $sel.find('option').filter(function(){ return normalize(this.getAttribute('data-bidang')) === bidang; })
                      .map(function(){ return this.value; }).get();
        var now = $sel.val() || [];
        $sel.val(Array.from(new Set(now.concat(picked)))).trigger('change');
      });
    }

    ensureJQandS2().then(function(){
      if(document.readyState!=='loading') init(); else document.addEventListener('DOMContentLoaded',init);
      window.addEventListener('load',init);
    });
  })();
  </script>
@else
  <script>
  (function(){
    function normalize(str){ return (str||'').toString().trim().toLowerCase(); }
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
      function setAll(on){ for (var i=0;i<boxes.length;i++){ boxes[i].checked=on; applyHighlight(boxes[i]); } }

      if(allBtn) allBtn.addEventListener('click', function(){ setAll(true); });
      if(clearB) clearB.addEventListener('click', function(){ setAll(false); });

      // Unit
      var unitBtns = document.querySelectorAll('[data-unit-pick]');
      for (var u=0; u<unitBtns.length; u++){
        unitBtns[u].addEventListener('click', function(){
          for (var k=0;k<unitBtns.length;k++){ unitBtns[k].classList.remove('active'); }
          this.classList.add('active');
          var unit = normalize(this.getAttribute('data-unit-pick'));
          for (var i=0;i<boxes.length;i++){
            var label = boxes[i].closest('label.check-item');
            var val = normalize(label ? label.getAttribute('data-unit') : '');
            if(val === unit){ boxes[i].checked = true; applyHighlight(boxes[i]); }
          }
        });
      }

      // Bidang
      var bidangBtns = document.querySelectorAll('[data-bidang-pick]');
      for (var b=0; b<bidangBtns.length; b++){
        bidangBtns[b].addEventListener('click', function(){
          for (var k=0;k<bidangBtns.length;k++){ bidangBtns[k].classList.remove('active'); }
          this.classList.add('active');
          var bidang = normalize(this.getAttribute('data-bidang-pick'));
          for (var i=0;i<boxes.length;i++){
            var label = boxes[i].closest('label.check-item');
            var val = normalize(label ? label.getAttribute('data-bidang') : '');
            if(val === bidang){ boxes[i].checked = true; applyHighlight(boxes[i]); }
          }
        });
      }

      for (var i=0;i<boxes.length;i++){
        applyHighlight(boxes[i]);
        (function(bx){
          bx.addEventListener('change', function(){ applyHighlight(bx); });
        })(boxes[i]);
      }
    }
    if(document.readyState!=='loading') initChecklist(); else document.addEventListener('DOMContentLoaded', initChecklist);
    window.addEventListener('load', initChecklist);
  })();
  </script>
@endif
@endpush
