{{-- resources/views/rapat/_form.blade.php --}}

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<style>
  /* ===== Select2 pills (multiple) – dark theme & rapi ===== */
  .select2-container--default .select2-selection--multiple{
    /* rapikan placeholder & chip agar sejajar */
    display:flex;
    align-items:center;
    flex-wrap:wrap;
    gap:6px;

    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.2);
    min-height: 40px;
    color:#fff;
    padding: 4px 6px;              /* ruang untuk placeholder */
    border-radius: 8px;
  }
  .select2-container--default .select2-selection--multiple .select2-selection__rendered{
    display:flex;
    align-items:center;
    flex-wrap:wrap;
    gap:6px;
    padding:0;
    margin:0;
    color:#fff;
  }
  /* input pencarian inline (tempat placeholder) — dinaikkan sedikit */
  .select2-container--default .select2-selection--multiple .select2-search--inline{
    margin:0;
  }
  .select2-container--default .select2-selection--multiple .select2-search--inline .select2-search__field{
    height:26px;
    line-height:26px;
    margin:0;
    padding:0 2px;
    color:#fff;
    background:transparent;
    border:none;
    outline:0;

    /* >>> naikkan placeholder sedikit */
    transform: translateY(-2px);
  }

  .select2-container--default .select2-selection--multiple .select2-selection__choice{
    background: rgba(255,255,255,.12);
    border-color: rgba(255,255,255,.25);
    color:#fff;
    font-weight: 500;
    border-radius: 20px;
    padding: 2px 8px;
    margin:0;
  }

  /* ikon close putih polos tanpa latar/kotak */
  .select2-container--default .select2-selection--multiple .select2-selection__choice__remove{
    color:#fff !important;
    background:transparent !important;
    border:none !important;
    box-shadow:none !important;
    margin-right:6px;
    font-weight:700;
    line-height:1;
  }
  .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover{
    color:#f87171 !important;
    background:transparent !important;
  }

  /* ===== Dropdown ===== */
  .select2-dropdown{ background:#111827; color:#fff; border:1px solid rgba(255,255,255,.18) }
  .select2-search--dropdown .select2-search__field{
    color:#fff; background:#1f2937; border:1px solid rgba(255,255,255,.18)
  }
  .select2-results__option{ color:#e5e7eb }
  .select2-results__option--highlighted{ background:#374151 !important; color:#fff }
  .select2-results__options{ max-height:320px !important; scrollbar-width:thin }
  .select2-results__options::-webkit-scrollbar{ height:8px; width:8px }
  .select2-results__options::-webkit-scrollbar-thumb{ background:#4b5563; border-radius:6px }

  /* ===== Input Focus ===== */
  .form-control:focus{
    background: rgba(255,255,255,.08);
    border-color: rgba(79,70,229,.55);
    box-shadow: 0 0 0 .15rem rgba(79,70,229,.25);
    color: #fff;
  }

  /* ===== Ikon input-group: putih polos tanpa latar ===== */
  .input-group-text{
    background: transparent !important;
    color: #fff !important;
    border: 1px solid rgba(255,255,255,.2);
  }

  /* ===== Toolbar di atas peserta ===== */
  .toolbar{ display:flex; align-items:center; gap:.5rem }
  .toolbar .btn{ padding:.35rem .6rem; border-radius:8px; font-weight:500 }
</style>
@endpush


{{-- ===== FORM FIELD ===== --}}
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

{{-- ====== Satu baris: Tanggal + Waktu + Tempat ====== --}}
<div class="form-row">
  <div class="form-group col-md-4">
    <label>Tanggal</label>
    <div class="input-group">
      <div class="input-group-prepend"></div>
      <input type="date" name="tanggal" value="{{ old('tanggal', $rapat->tanggal ?? '') }}" class="form-control" required>
    </div>
  </div>
  <div class="form-group col-md-4">
    <label>Waktu Mulai</label>
    <div class="input-group">
      <div class="input-group-prepend"></div>
      <input type="time" name="waktu_mulai" value="{{ old('waktu_mulai', $rapat->waktu_mulai ?? '') }}" class="form-control" required>
    </div>
  </div>
  <div class="form-group col-md-4">
    <label>Tempat</label>
    <div class="input-group">
      <div class="input-group-prepend"></div>
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

{{-- ====== Approval 1 & 2 (Satu Baris) ====== --}}
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

{{-- ====== Peserta (Select2 Multiple) ====== --}}
@php
  $selectedIds = [];
  $selectedLabels = [];
  if (is_array(old('peserta'))) {
      $selectedIds = array_values(array_unique(array_map('intval', old('peserta'))));
  } elseif (!empty($peserta_terpilih)) {
      if (is_array($peserta_terpilih) && isset($peserta_terpilih[0]) && is_array($peserta_terpilih[0]) && array_key_exists('id',$peserta_terpilih[0])) {
          foreach ($peserta_terpilih as $p) {
              $selectedIds[] = (int)$p['id'];
              if (!empty($p['text'])) $selectedLabels[(int)$p['id']] = $p['text'];
          }
      } else {
          $selectedIds = array_values(array_unique(array_map('intval', $peserta_terpilih)));
      }
  }
  $labelsFromMaster = collect($daftar_peserta)->keyBy('id')->map(fn($u) => $u->name)->all();
  foreach ($selectedIds as $sid) {
      if (!isset($selectedLabels[$sid]) && isset($labelsFromMaster[$sid])) $selectedLabels[$sid] = $labelsFromMaster[$sid];
  }
  $selectedSet = array_flip($selectedIds);
@endphp

<div class="d-flex align-items-center justify-content-between mb-1">
  <label class="mb-0">Peserta</label>
  <div class="toolbar">
    <button type="button" class="btn btn-outline-light btn-sm" id="btnSelectAll">
      <i class="fas fa-check-double mr-1"></i> Pilih semua
    </button>
    <button type="button" class="btn btn-outline-light btn-sm" id="btnClearAll">
      <i class="fas fa-times mr-1"></i> Hapus semua
    </button>
  </div>
</div>

<div class="form-group" id="{{ $pesertaWrapperId ?? 'peserta-wrapper' }}">
  <select
    id="pesertaSelect"
    name="peserta[]"
    multiple
    style="width:100%;"
    data-placeholder="Pilih peserta rapat"
  >
    {{-- preselected --}}
    @foreach($selectedIds as $sid)
      <option value="{{ $sid }}" selected>{{ $selectedLabels[$sid] ?? ($labelsFromMaster[$sid] ?? ('User #'.$sid)) }}</option>
    @endforeach
    {{-- master option --}}
    @foreach($daftar_peserta as $peserta)
      @continue(isset($selectedSet[$peserta->id]))
      <option value="{{ $peserta->id }}">{{ $labelsFromMaster[$peserta->id] ?? $peserta->name }}</option>
    @endforeach
  </select>
  <small class="text-muted d-block mt-1">Tip: ketik untuk mencari nama peserta</small>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
<script>
(function(){
  const $sel      = $('#pesertaSelect');
  const $btnAll   = $('#btnSelectAll');
  const $btnClear = $('#btnClearAll');

  // Hapus duplikasi (mis. re-init di modal)
  if ($sel.hasClass('select2-hidden-accessible')) { try { $sel.select2('destroy'); } catch(e){} }
  $sel.next('.select2-container').remove();

  // Init Select2
  $sel.select2({
    width: '100%',
    placeholder: $sel.data('placeholder') || 'Pilih peserta rapat',
    closeOnSelect: false
  });

  // Pilih semua / Hapus semua
  $btnAll.on('click', function(){
    const allVals = [];
    $sel.find('option').each(function(){ this.selected = true; allVals.push(this.value); });
    $sel.val(allVals).trigger('change');
  });
  $btnClear.on('click', function(){
    $sel.find('option').prop('selected', false);
    $sel.val([]).trigger('change');
  });
})();
</script>
@endpush
