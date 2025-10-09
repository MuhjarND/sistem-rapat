{{-- resources/views/rapat/_form.blade.php --}}

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

<div class="form-group">
    <label>Tanggal</label>
    <input type="date" name="tanggal"
        value="{{ old('tanggal', $rapat->tanggal ?? '') }}"
        class="form-control" required>
</div>

<div class="form-group">
    <label>Waktu Mulai</label>
    <input type="time" name="waktu_mulai"
        value="{{ old('waktu_mulai', $rapat->waktu_mulai ?? '') }}"
        class="form-control" required>
</div>

<div class="form-group">
    <label>Tempat</label>
    <input type="text" name="tempat"
        value="{{ old('tempat', $rapat->tempat ?? '') }}"
        class="form-control" required>
</div>

<div class="form-group">
    <label>Kategori</label>
    <select name="id_kategori" class="form-control" required
            @isset($dropdownParentId) data-dropdown-parent="{{ $dropdownParentId }}" @endisset>
        <option value="">-- Pilih Kategori --</option>
        @foreach($daftar_kategori as $kategori)
            <option value="{{ $kategori->id }}"
                {{ old('id_kategori', $rapat->id_kategori ?? '') == $kategori->id ? 'selected' : '' }}>
                {{ $kategori->nama }}
            </option>
        @endforeach
    </select>
</div>

{{-- === Approval 1 & Approval 2 === --}}
<div class="form-group">
    <label>Approval 1 <span class="text-danger">*</span></label>
    <select name="approval1_user_id" class="form-control" required
            @isset($dropdownParentId) data-dropdown-parent="{{ $dropdownParentId }}" @endisset>
        <option value="">-- Pilih Approval 1 --</option>
        @foreach($approval1_list as $u)
            <option value="{{ $u->id }}"
                {{ old('approval1_user_id', $rapat->approval1_user_id ?? '') == $u->id ? 'selected' : '' }}>
                {{ $u->name }} {{ $u->tingkatan ? '(T'.$u->tingkatan.')' : '' }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label>Approval 2 (opsional)</label>
    <select name="approval2_user_id" class="form-control"
            @isset($dropdownParentId) data-dropdown-parent="{{ $dropdownParentId }}" @endisset>
        <option value="">-- Tanpa Approval 2 --</option>
        @foreach($approval2_list as $u)
            <option value="{{ $u->id }}"
                {{ old('approval2_user_id', $rapat->approval2_user_id ?? '') == $u->id ? 'selected' : '' }}>
                {{ $u->name }} (T2)
            </option>
        @endforeach
    </select>
</div>

{{-- ============ Peserta (Multiple Select2) ============ --}}
@php
    // 1) Ambil selected dari old() atau dari $peserta_terpilih
    $selectedIds = [];
    $selectedLabels = []; // [id => text]

    $oldPeserta = old('peserta');
    if (is_array($oldPeserta)) {
        // old() berisi array id
        $selectedIds = array_values(array_unique(array_map('intval', $oldPeserta)));
    } else {
        // dari $peserta_terpilih: bisa [id,...] atau [{id,text},...]
        if (!empty($peserta_terpilih)) {
            // bentuk objek?
            if (is_array($peserta_terpilih) && isset($peserta_terpilih[0]) && is_array($peserta_terpilih[0]) && array_key_exists('id', $peserta_terpilih[0])) {
                foreach ($peserta_terpilih as $p) {
                    $selectedIds[] = (int)$p['id'];
                    if (!empty($p['text'])) $selectedLabels[(int)$p['id']] = $p['text'];
                }
            } else {
                // diasumsikan array id
                $selectedIds = array_values(array_unique(array_map('intval', $peserta_terpilih)));
            }
        }
    }

    // 2) Buat map label dari $daftar_peserta untuk fallback
    $labelsFromMaster = collect($daftar_peserta)->keyBy('id')->map(function($u){
        $label = $u->name;
        if (!empty($u->jabatan)) $label .= ' — '.$u->jabatan;
        if (!empty($u->unit))    $label .= ' · '.$u->unit;
        return $label;
    })->all();

    // Isi label yang belum ada memakai master
    foreach ($selectedIds as $sid) {
        if (!isset($selectedLabels[$sid]) && isset($labelsFromMaster[$sid])) {
            $selectedLabels[$sid] = $labelsFromMaster[$sid];
        }
    }

    // 3) Siapkan set untuk menghindari duplikasi opsi
    $selectedSet = array_flip($selectedIds);
@endphp

<div class="form-group" id="{{ $pesertaWrapperId ?? 'peserta-wrapper' }}">
    <label>Peserta</label>
    <select
        class="js-example-basic-multiple"
        name="peserta[]"
        multiple
        style="width:100%;"
        required
        data-dropdown-parent="#{{ $pesertaWrapperId ?? 'peserta-wrapper' }}"
    >
        {{-- Render dulu yang terpilih agar tampil preselected, walau tidak ada di $daftar_peserta --}}
        @foreach($selectedIds as $sid)
            <option value="{{ $sid }}" selected>
                {{ $selectedLabels[$sid] ?? ($labelsFromMaster[$sid] ?? ('User #'.$sid)) }}
            </option>
        @endforeach

        {{-- Lalu render opsi master yang belum termasuk selected --}}
        @foreach($daftar_peserta as $peserta)
            @continue(isset($selectedSet[$peserta->id]))
            <option value="{{ $peserta->id }}">
                {{ $labelsFromMaster[$peserta->id] ?? $peserta->name }}
            </option>
        @endforeach
    </select>
    <small class="text-muted d-block mt-1">Tip: ketik untuk mencari nama peserta</small>
</div>

@push('scripts')
<script>
(function(){
  // Inisialisasi Select2 untuk elemen di partial ini
  const wrapperId = '#{{ $pesertaWrapperId ?? 'peserta-wrapper' }}';
  const $wrap = $(wrapperId);
  if ($wrap.length){
    const $sel = $wrap.find('select.js-example-basic-multiple');
    if ($sel.length && !$sel.data('select2')){
      $sel.select2({
        width: '100%',
        dropdownParent: $wrap.closest('.modal').length ? $wrap.closest('.modal') : $(wrapperId)
      });
    }
  }
})();
</script>
@endpush
