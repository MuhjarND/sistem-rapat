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
    <select name="id_kategori" class="form-control" required>
        <option value="">-- Pilih Kategori --</option>
        @foreach($daftar_kategori as $kategori)
            <option value="{{ $kategori->id }}"
                {{ old('id_kategori', $rapat->id_kategori ?? '') == $kategori->id ? 'selected' : '' }}>
                {{ $kategori->nama }}
            </option>
        @endforeach
    </select>
</div>
<div class="form-group">
    <label>Pimpinan</label>
    <select name="id_pimpinan" class="form-control" required>
        <option value="">-- Pilih Pimpinan --</option>
        @foreach($daftar_pimpinan as $pimpinan)
            <option value="{{ $pimpinan->id }}"
                {{ old('id_pimpinan', $rapat->id_pimpinan ?? '') == $pimpinan->id ? 'selected' : '' }}>
                {{ $pimpinan->nama }} - {{ $pimpinan->jabatan }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-group" id="{{ $pesertaWrapperId ?? 'peserta-wrapper' }}">
    <label>Peserta</label>
    <select
        class="js-example-basic-multiple"
        name="peserta[]"
        multiple="multiple"
        style="width: 100%;"
        required
        {{-- kunci parent dropdown ke wrapper ini --}}
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
