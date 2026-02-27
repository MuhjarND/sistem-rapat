@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Edit Rapat</h3>
    <div class="card">
        <div class="card-body">
            <form action="{{ route('rapat.update', $rapat->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label>Nomor Undangan</label>
                    <input type="text" name="nomor_undangan" class="form-control" required value="{{ old('nomor_undangan', $rapat->nomor_undangan) }}">
                </div>
                <div class="form-group">
                    <label>Judul</label>
                    <input type="text" name="judul" class="form-control" required value="{{ old('judul', $rapat->judul) }}">
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" class="form-control">{{ old('deskripsi', $rapat->deskripsi) }}</textarea>
                </div>
                @php $detailValue = old('pakai_detail_tambahan', !empty($rapat->detail_tambahan) ? '1' : '0'); @endphp
                <div class="form-group">
                    <label>Detail Tambahan Surat</label>
                    <div class="d-flex flex-wrap">
                        <div class="form-check mr-3">
                            <input class="form-check-input js-detail-check" type="radio" name="pakai_detail_tambahan" id="detail-tidak-edit" value="0"
                                   data-detail-wrap="detailTambahanWrap-edit" {{ (string) $detailValue === '0' ? 'checked' : '' }}>
                            <label class="form-check-label" for="detail-tidak-edit">Tidak</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input js-detail-check" type="radio" name="pakai_detail_tambahan" id="detail-ya-edit" value="1"
                                   data-detail-wrap="detailTambahanWrap-edit" {{ (string) $detailValue === '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="detail-ya-edit">Ya</label>
                        </div>
                    </div>
                </div>
                <div class="form-group" id="detailTambahanWrap-edit" style="display:none;">
                    <label>Isi Detail Tambahan</label>
                    <textarea name="detail_tambahan" class="form-control" rows="3"
                              placeholder="Contoh: Ketentuan khusus, informasi tambahan kegiatan, atau catatan penting lainnya.">{{ old('detail_tambahan', $rapat->detail_tambahan ?? '') }}</textarea>
                    <small class="form-text text-muted">Akan ditampilkan di undangan sebelum kalimat memohon kehadiran.</small>
                    @error('detail_tambahan') <div class="text-danger mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label>Kategori Rapat</label>
                    <select name="id_kategori" class="form-control js-kategori-select" data-pakaian-wrap="jenisPakaianWrap-edit" required>
                        <option value="">-- Pilih Kategori --</option>
                        @foreach($daftar_kategori as $kategori)
                            <option value="{{ $kategori->id }}"
                                data-nama="{{ $kategori->nama }}"
                                data-butuh-pakaian="{{ !empty($kategori->butuh_pakaian) ? '1' : '0' }}"
                                {{ old('id_kategori', $rapat->id_kategori ?? '') == $kategori->id ? 'selected' : '' }}>
                                {{ $kategori->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <div class="form-check">
                        <input class="form-check-input js-virtual-check" type="checkbox" name="is_virtual" id="virtual-check-edit" value="1"
                               data-virtual-wrap="linkZoomWrap-edit" {{ old('is_virtual', $rapat->is_virtual ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="virtual-check-edit">Virtual</label>
                    </div>
                </div>
                <div class="form-group" id="linkZoomWrap-edit" style="display:none;">
                    <label>Meeting ID</label>
                    <input type="text" name="meeting_id" class="form-control"
                           placeholder="Contoh: 123 456 7890"
                           value="{{ old('meeting_id', $rapat->meeting_id ?? '') }}">
                    <small class="form-text text-muted">Isi Meeting ID dan Passcode untuk rapat virtual.</small>
                    <div class="mt-2">
                        <label>Passcode</label>
                        <input type="text" name="meeting_passcode" class="form-control"
                               placeholder="Contoh: 123456"
                               value="{{ old('meeting_passcode', $rapat->meeting_passcode ?? '') }}">
                    </div>
                </div>
                <div class="form-group" id="jenisPakaianWrap-edit" style="display:none;">
                    <label>Jenis Pakaian</label>
                    <input type="text" name="jenis_pakaian" class="form-control"
                           placeholder="Contoh: Seragam PDH, Batik, Pakaian Dinas"
                           value="{{ old('jenis_pakaian', $rapat->jenis_pakaian ?? '') }}">
                    <small class="form-text text-muted">Diisi jika kategori mewajibkan pakaian.</small>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" required value="{{ old('tanggal', $rapat->tanggal) }}">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Waktu Mulai</label>
                        <input type="time" name="waktu_mulai" class="form-control" required value="{{ old('waktu_mulai', $rapat->waktu_mulai) }}">
                    </div>
                </div>
                <div class="form-group">
                    <label>Tempat</label>
                    <input type="text" name="tempat" class="form-control" required value="{{ old('tempat', $rapat->tempat) }}">
                </div>
                <div class="form-group">
                    <label>Pimpinan Rapat</label>
                    <select name="id_pimpinan" class="form-control" required>
                        <option value="">-- Pilih Pimpinan --</option>
                        @foreach($daftar_pimpinan as $pimpinan)
                            <option value="{{ $pimpinan->id }}"
                            @if(old('id_pimpinan', $rapat->id_pimpinan ?? '') == $pimpinan->id) selected @endif>
                            {{ $pimpinan->nama }} - {{ $pimpinan->jabatan }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Pilih Peserta Undangan</label>
                    <div class="card p-2" style="max-height:220px;overflow:auto;">
                        @foreach($daftar_peserta as $peserta)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="peserta[]" value="{{ $peserta->id }}"
                                id="peserta{{ $peserta->id }}"
                                {{ in_array($peserta->id, $peserta_terpilih) ? 'checked' : '' }}>
                            <label class="form-check-label" for="peserta{{ $peserta->id }}">{{ $peserta->name }} ({{ $peserta->email }})</label>
                        </div>
                        @endforeach
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Update Rapat</button>
                <a href="{{ route('rapat.index') }}" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  document.querySelectorAll('.js-detail-check').forEach(function(input){
    var wrapId = input.getAttribute('data-detail-wrap');
    var wrap = wrapId ? document.getElementById(wrapId) : null;
    if (!wrap) return;
    var textArea = wrap.querySelector('textarea[name="detail_tambahan"]');
    function sync(){
      var checked = document.querySelector('.js-detail-check:checked');
      var show = checked && checked.value === '1';
      wrap.style.display = show ? '' : 'none';
      if (textArea) {
        if (show) textArea.setAttribute('required','required');
        else textArea.removeAttribute('required');
      }
    }
    input.addEventListener('change', sync);
    sync();
  });
})();
</script>
<script>
(function(){
  document.querySelectorAll('select.js-kategori-select').forEach(function(sel){
    var wrapId = sel.getAttribute('data-pakaian-wrap');
    var wrap = wrapId ? document.getElementById(wrapId) : null;
    if (!wrap) return;
    var input = wrap.querySelector('input[name="jenis_pakaian"]');
    function isRequiredByCategory(){
      var opt = sel.options[sel.selectedIndex];
      if (!opt) return false;
      return (opt.getAttribute('data-butuh-pakaian') || '0') === '1';
    }
    function sync(){
      var show = isRequiredByCategory();
      wrap.style.display = show ? '' : 'none';
      if (input) {
        if (show) input.setAttribute('required','required');
        else input.removeAttribute('required');
      }
    }
    sel.addEventListener('change', sync);
    sync();
  });
})();
</script>
<script>
(function(){
  document.querySelectorAll('.js-virtual-check').forEach(function(cb){
    var wrapId = cb.getAttribute('data-virtual-wrap');
    var wrap = wrapId ? document.getElementById(wrapId) : null;
    if (!wrap) return;
    var inputId = wrap.querySelector('input[name="meeting_id"]');
    var inputPass = wrap.querySelector('input[name="meeting_passcode"]');
    function sync(){
      var show = cb.checked;
      wrap.style.display = show ? '' : 'none';
      if (inputId) {
        if (show) inputId.setAttribute('required','required');
        else inputId.removeAttribute('required');
      }
      if (inputPass) {
        if (show) inputPass.setAttribute('required','required');
        else inputPass.removeAttribute('required');
      }
    }
    cb.addEventListener('change', sync);
    sync();
  });
})();
</script>
@endpush
