@extends('layouts.app')
@section('content')
<div class="container">
    <h3>Edit Notulensi</h3>

    {{-- HEADER INSTANSI --}}
    <div class="mb-2 p-3 border rounded" style="background:#f6f6f6;">
        <strong>MAHKAMAH AGUNG REPUBLIK INDONESIA<br>
        PENGADILAN TINGGI AGAMA PAPUA BARAT</strong><br>
        Kode Dokumen: <strong>FM/AM/04/01</strong>
    </div>

    {{-- INFO RAPAT (readonly) --}}
    <div class="row mb-3">
        <div class="col-md-6">
            <label>Judul Rapat</label>
            <input type="text" class="form-control" value="{{ $rapat->judul }}" readonly>
        </div>
        <div class="col-md-6">
            <label>Hari/Tanggal/Jam</label>
            <input type="text" class="form-control"
                   value="{{ \Carbon\Carbon::parse($rapat->tanggal)->translatedFormat('l, d F Y') }} {{ $rapat->waktu_mulai }}"
                   readonly>
        </div>
        <div class="col-md-6 mt-2">
            <label>Tempat</label>
            <input type="text" class="form-control" value="{{ $rapat->tempat }}" readonly>
        </div>
        <div class="col-md-6 mt-2">
            <label>Pimpinan Rapat</label>
            <input type="text" class="form-control" value="{{ $rapat->jabatan_pimpinan ?? '-' }}" readonly>
        </div>
        <div class="col-md-6 mt-2">
            <label>Agenda Rapat</label>
            <input type="text" class="form-control" value="{{ $rapat->deskripsi ?: $rapat->judul }}" readonly>
        </div>
    </div>

    <form id="form-notulensi" action="{{ route('notulensi.update', $notulensi->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @if ($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        {{-- TABEL PEMBAHASAN --}}
        <hr>
        <h5>Pembahasan & Rangkaian Acara</h5>

        <table class="table table-bordered" id="tabel-detail">
            <thead>
                <tr>
                    <th style="width:60px">No</th>
                    <th>Hasil Monitoring & Evaluasi / Rangkaian Acara</th>
                    <th>Rekomendasi Tindak Lanjut</th>
                    <th style="width:200px">Penanggung Jawab</th>
                    <th style="width:170px">Tgl. Penyelesaian</th>
                    <th style="width:90px">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @php $idx = 0; @endphp
            @forelse($detail as $row)
                <tr>
                    <td class="no">{{ $loop->iteration }}</td>
                    <td>
                        <textarea name="baris[{{ $idx }}][hasil_pembahasan]" class="form-control rich required-rich">{!! $row->hasil_pembahasan !!}</textarea>
                    </td>
                    <td>
                        <textarea name="baris[{{ $idx }}][rekomendasi]" class="form-control rich">{!! $row->rekomendasi !!}</textarea>
                    </td>
                    <td>
                        <input type="text" name="baris[{{ $idx }}][penanggung_jawab]" class="form-control" value="{{ $row->penanggung_jawab }}">
                    </td>
                    <td>
                        <input type="date" name="baris[{{ $idx }}][tgl_penyelesaian]" class="form-control" value="{{ $row->tgl_penyelesaian }}">
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm" onclick="hapusBaris(this)">Hapus</button>
                    </td>
                </tr>
                @php $idx++; @endphp
            @empty
                <tr>
                    <td class="no">1</td>
                    <td><textarea name="baris[0][hasil_pembahasan]" class="form-control rich required-rich"></textarea></td>
                    <td><textarea name="baris[0][rekomendasi]" class="form-control rich"></textarea></td>
                    <td><input type="text" name="baris[0][penanggung_jawab]" class="form-control"></td>
                    <td><input type="date" name="baris[0][tgl_penyelesaian]" class="form-control"></td>
                    <td><button type="button" class="btn btn-danger btn-sm" onclick="hapusBaris(this)">Hapus</button></td>
                </tr>
                @php $idx = 1; @endphp
            @endforelse
            </tbody>
        </table>
        <button type="button" id="btn-tambah-baris" class="btn btn-success btn-sm">Tambah Baris</button>

        {{-- CKEditor 5 --}}
        <script src="https://cdn.ckeditor.com/ckeditor5/41.2.0/classic/ckeditor.js"></script>
        <style>.ck-editor__editable { min-height: 140px; }</style>

        {{-- DOKUMENTASI --}}
        <hr>
        <h5>Dokumentasi Kegiatan</h5>

        @if(count($dokumentasi))
        <div class="row mb-3">
            @foreach($dokumentasi as $dok)
              <div class="col-md-4 mb-3">
                <div class="border p-2 h-100">
                  <img src="{{ asset($dok->file_path) }}" class="img-fluid rounded mb-2" alt="dok">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="hapus_dok[]" value="{{ $dok->id }}" id="dok{{ $dok->id }}">
                    <label class="form-check-label" for="dok{{ $dok->id }}">Hapus foto ini</label>
                  </div>
                </div>
              </div>
            @endforeach
        </div>
        @endif

        <div class="mb-3">
            <label>Tambah Foto Baru (boleh lebih dari satu)</label>
            <input type="file" name="dokumentasi_baru[]" class="form-control" accept="image/*" multiple>
            <small class="text-muted">Maks 10MB per file.</small>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="{{ route('notulensi.show', $notulensi->id) }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

{{-- ====== SCRIPT: CKEditor + Tambah/Hapus Baris + Sinkron saat Submit ====== --}}
<script>
(function(){
  let idxBaris = {{ $idx ?? 0 }};                 // lanjutan index baris berikutnya
  const editors = new Map();

  function initEditors(scope = document){
    scope.querySelectorAll('textarea.rich:not(.ck-ready)').forEach(el => {
      ClassicEditor.create(el, {
        toolbar: ['undo','redo','|','bold','italic','underline','|','bulletedList','numberedList','outdent','indent','|','link','insertTable']
      }).then(editor => {
        editors.set(el, editor);
        el.classList.add('ck-ready');
      }).catch(console.error);
    });
  }

  function destroyEditorsInside(node){
    node.querySelectorAll('textarea.rich.ck-ready').forEach(el => {
      const inst = editors.get(el);
      if (inst) { inst.destroy().catch(()=>{}); editors.delete(el); }
      el.classList.remove('ck-ready');
    });
  }

  function renumber(){
    const rows = document.querySelectorAll('#tabel-detail tbody tr');
    rows.forEach((tr, i) => {
      tr.querySelector('.no').textContent = i + 1;
      tr.querySelectorAll('textarea, input').forEach(input => {
        if (!input.name) return;
        input.name = input.name.replace(/baris\[\d+\]/, 'baris[' + i + ']');
      });
    });
    idxBaris = rows.length;
  }

  document.getElementById('btn-tambah-baris').addEventListener('click', () => {
    const tbody = document.querySelector('#tabel-detail tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="no"></td>
      <td><textarea name="baris[${idxBaris}][hasil_pembahasan]" class="form-control rich required-rich"></textarea></td>
      <td><textarea name="baris[${idxBaris}][rekomendasi]" class="form-control rich"></textarea></td>
      <td><input type="text" name="baris[${idxBaris}][penanggung_jawab]" class="form-control" placeholder="Kesekretariatan"></td>
      <td><input type="date" name="baris[${idxBaris}][tgl_penyelesaian]" class="form-control"></td>
      <td><button type="button" class="btn btn-danger btn-sm" onclick="hapusBaris(this)">Hapus</button></td>
    `;
    tbody.appendChild(tr);
    initEditors(tr);
    renumber();
  });

  window.hapusBaris = function(btn){
    const tr = btn.closest('tr');
    destroyEditorsInside(tr);
    tr.remove();
    renumber();
  }

  document.getElementById('form-notulensi').addEventListener('submit', function(e){
    // sinkron CKEditor -> textarea
    editors.forEach((editor, el) => { el.value = editor.getData(); });

    // validasi minimal kolom wajib
    let invalid = false;
    this.querySelectorAll('textarea.required-rich').forEach(el => {
      const plain = (editors.get(el)?.getData() || '').replace(/<[^>]*>/g,'').trim();
      if (!plain) {
        invalid = true;
        el.closest('td').style.boxShadow = 'inset 0 0 0 2px #dc3545';
      }
    });

    if (invalid) {
      e.preventDefault();
      alert('Mohon lengkapi kolom "Hasil Monitoring & Evaluasi / Rangkaian Acara" yang wajib diisi.');
      return false;
    }
  });

  document.addEventListener('DOMContentLoaded', () => initEditors());
})();
</script>
@endsection
