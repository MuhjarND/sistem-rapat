@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Tambah Rapat Baru</h3>
    <div class="card">
        <div class="card-body">
            <form action="{{ route('rapat.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label>Nomor Undangan</label>
                    <input type="text" name="nomor_undangan" class="form-control" required value="{{ old('nomor_undangan') }}">
                </div>
                <div class="form-group">
                    <label>Judul</label>
                    <input type="text" name="judul" class="form-control" required value="{{ old('judul') }}">
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" class="form-control">{{ old('deskripsi') }}</textarea>
                </div>
                <div class="form-group">
                    <label>Kategori Rapat</label>
                    <select name="id_kategori" class="form-control js-kategori-select" data-pakaian-wrap="jenisPakaianWrap-create" required>
                        <option value="">-- Pilih Kategori --</option>
                        @foreach($daftar_kategori as $kategori)
                            <option value="{{ $kategori->id }}"
                                data-nama="{{ $kategori->nama }}"
                                {{ old('id_kategori', $rapat->id_kategori ?? '') == $kategori->id ? 'selected' : '' }}>
                                {{ $kategori->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <div class="form-check">
                        <input class="form-check-input js-virtual-check" type="checkbox" name="is_virtual" id="virtual-check-create" value="1"
                               data-virtual-wrap="linkZoomWrap-create" {{ old('is_virtual') ? 'checked' : '' }}>
                        <label class="form-check-label" for="virtual-check-create">Virtual</label>
                    </div>
                </div>
                <div class="form-group" id="linkZoomWrap-create" style="display:none;">
                    <label>Meeting ID</label>
                    <input type="text" name="meeting_id" class="form-control"
                           placeholder="Contoh: 123 456 7890"
                           value="{{ old('meeting_id') }}">
                    <small class="form-text text-muted">Isi Meeting ID dan Passcode untuk rapat virtual.</small>
                    <div class="mt-2">
                        <label>Passcode</label>
                        <input type="text" name="meeting_passcode" class="form-control"
                               placeholder="Contoh: 123456"
                               value="{{ old('meeting_passcode') }}">
                    </div>
                </div>
                <div class="form-group" id="jenisPakaianWrap-create" style="display:none;">
                    <label>Jenis Pakaian</label>
                    <input type="text" name="jenis_pakaian" class="form-control"
                           placeholder="Contoh: Seragam PDH, Batik, Pakaian Dinas"
                           value="{{ old('jenis_pakaian') }}">
                    <small class="form-text text-muted">Diisi untuk kategori Penandatanganan Pakta Integritas dan Komitmen Bersama atau Buka Bersama.</small>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" required value="{{ old('tanggal') }}">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Waktu Mulai</label>
                        <input type="time" name="waktu_mulai" class="form-control" required value="{{ old('waktu_mulai') }}">
                    </div>
                </div>
                <div class="form-group">
                    <label>Tempat</label>
                    <input type="text" name="tempat" class="form-control" required value="{{ old('tempat') }}">
                </div>
                <div class="form-group">
                    <label>Lampiran Tambahan</label>
                    <div class="d-flex flex-wrap">
                        <div class="form-check mr-3">
                            <input class="form-check-input js-lampiran-check" type="radio" name="lampiran_tambahan" id="lampiran-tidak" value="0"
                                   data-lampiran-wrap="lampiranTambahanWrap-create" {{ old('lampiran_tambahan', '0') === '0' ? 'checked' : '' }}>
                            <label class="form-check-label" for="lampiran-tidak">Tidak</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input js-lampiran-check" type="radio" name="lampiran_tambahan" id="lampiran-ya" value="1"
                                   data-lampiran-wrap="lampiranTambahanWrap-create" {{ old('lampiran_tambahan') === '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="lampiran-ya">Ya</label>
                        </div>
                    </div>
                </div>
                <div class="form-group" id="lampiranTambahanWrap-create" style="display:none;">
                    <label>Upload Dokumen Tambahan</label>
                    <input type="file" name="lampiran_tambahan_file" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
                    <small class="form-text text-muted">Maks 20MB. Format: PDF/DOC/DOCX/XLS/XLSX/PPT/PPTX.</small>
                    @error('lampiran_tambahan_file') <div class="text-danger mt-1">{{ $message }}</div> @enderror
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
                            <input class="form-check-input" type="checkbox" name="peserta[]" value="{{ $peserta->id }}" id="peserta{{ $peserta->id }}">
                            <label class="form-check-label" for="peserta{{ $peserta->id }}">{{ $peserta->name }} ({{ $peserta->email }})</label>
                        </div>
                        @endforeach
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Simpan & Kirim Undangan</button>
                <a href="{{ route('rapat.index') }}" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  var targets = [
    'penandatanganan pakta integritas dan komitmen bersama',
    'buka bersama'
  ];
  document.querySelectorAll('select.js-kategori-select').forEach(function(sel){
    var wrapId = sel.getAttribute('data-pakaian-wrap');
    var wrap = wrapId ? document.getElementById(wrapId) : null;
    if (!wrap) return;
    var input = wrap.querySelector('input[name="jenis_pakaian"]');
    function getSelectedName(){
      var opt = sel.options[sel.selectedIndex];
      if (!opt) return '';
      return (opt.getAttribute('data-nama') || opt.textContent || '').toString();
    }
    function sync(){
      var name = getSelectedName().trim().toLowerCase();
      var show = (targets.indexOf(name) !== -1) || (name.indexOf('buka bersama') !== -1);
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
<script>
(function(){
  document.querySelectorAll('.js-lampiran-check').forEach(function(input){
    var wrapId = input.getAttribute('data-lampiran-wrap');
    var wrap = wrapId ? document.getElementById(wrapId) : null;
    if (!wrap) return;
    var fileInput = wrap.querySelector('input[name="lampiran_tambahan_file"]');
    function sync(){
      var checked = document.querySelector('.js-lampiran-check:checked');
      var show = checked && checked.value === '1';
      wrap.style.display = show ? '' : 'none';
      if (fileInput) {
        if (show) fileInput.setAttribute('required','required');
        else fileInput.removeAttribute('required');
      }
    }
    input.addEventListener('change', sync);
    sync();
  });
})();
</script>
@endpush
