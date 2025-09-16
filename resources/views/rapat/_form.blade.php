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

{{-- === GANTI: Pimpinan -> Approval 1 & Approval 2 === --}}
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
{{-- ================================================ --}}

<div class="form-group" id="{{ $pesertaWrapperId ?? 'peserta-wrapper' }}">
    <label>Peserta</label>
    <select
        class="js-example-basic-multiple"
        name="peserta[]"
        multiple="multiple"
        style="width: 100%;"
        required
        data-dropdown-parent="#{{ $pesertaWrapperId ?? 'peserta-wrapper' }}"
    >
        @foreach($daftar_peserta as $peserta)
            <option value="{{ $peserta->id }}"
                {{ in_array($peserta->id, old('peserta', $peserta_terpilih ?? [])) ? 'selected' : '' }}>
                {{ $peserta->name }}
            </option>
        @endforeach
    </select>
</div>
