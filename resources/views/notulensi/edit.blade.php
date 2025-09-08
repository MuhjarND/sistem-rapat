@extends('layouts.app')
@section('title','Edit Notulensi')

@section('style')
<style>
  /* ====== INPUTS & FORM THEME ====== */
  .form-control,
  .custom-select{
    background: rgba(255,255,255,.06);
    border: 1px solid var(--border);
    color: var(--text);
    border-radius: 10px;
  }
  .form-control:focus,
  .custom-select:focus{
    background: rgba(255,255,255,.08);
    border-color: rgba(79,70,229,.55);
    box-shadow: 0 0 0 .15rem rgba(79,70,229,.25);
    color: var(--text);
  }
  .form-control[readonly]{
    background: rgba(255,255,255,.08) !important;
    color: var(--text) !important;
    border-color: var(--border);
  }
  ::placeholder{ color: #9fb0cd !important; opacity:.75; }

  /* ====== TABLE ====== */
  #tabel-detail thead th{
    background: linear-gradient(180deg, rgba(79,70,229,.22), rgba(14,165,233,.18));
    border-bottom: 1px solid var(--border);
    font-weight: 700;
    color: var(--text);
    vertical-align: middle;
  }
  #tabel-detail td, #tabel-detail th{ border-color: var(--border); }
  #tabel-detail .no{ text-align:center; font-weight:700; }

  /* ====== CKEditor DARK ====== */
  .ck.ck-editor{
    border-radius: 12px;
    border: 1px solid var(--border);
    background: #0f1533;
    box-shadow: var(--shadow);
  }
  .ck.ck-editor .ck.ck-toolbar{
    background: linear-gradient(180deg, rgba(17,24,39,.85), rgba(17,24,39,.65));
    border-bottom: 1px solid var(--border);
  }
  .ck.ck-editor .ck.ck-toolbar .ck-button{ color: var(--text); }
  .ck.ck-editor .ck.ck-toolbar .ck-button:hover{
    background: rgba(79,70,229,.18);
    color:#fff;
    border-radius: 8px;
  }
  .ck.ck-editor__editable_inline{
    background: #0d1330;
    color: var(--text);
    min-height: 160px;
  }
  .ck-content a{ color:#93c5fd; }

  .btn-sm{ border-radius: 8px; padding:.25rem .55rem; font-weight:600; }

  .panel-soft{
    background: linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,.02));
    border: 1px solid var(--border);
    border-radius: 14px;
    box-shadow: var(--shadow);
    color: var(--text);
  }
</style>
@endsection

@section('content')
<div class="container">
  <h3 class="mb-3">Edit Notulensi</h3>

  {{-- HEADER INSTANSI --}}
  <div class="panel-soft p-3 mb-3">
    <strong>
      MAHKAMAH AGUNG REPUBLIK INDONESIA<br>
      PENGADILAN TINGGI AGAMA PAPUA BARAT
    </strong><br>
    Kode Dokumen: <strong>FM/AM/04/01</strong>
  </div>

  {{-- INFO RAPAT (readonly) --}}
  <div class="card mb-3">
    <div class="card-header"><b>Informasi Rapat</b></div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group col-md-6">
          <label>Judul Rapat</label>
          <input type="text" class="form-control" value="{{ $rapat->judul }}" readonly>
        </div>
        <div class="form-group col-md-6">
          <label>Hari/Tanggal/Jam</label>
          <input type="text" class="form-control"
                 value="{{ \Carbon\Carbon::parse($rapat->tanggal)->translatedFormat('l, d F Y') }} {{ $rapat->waktu_mulai }}"
                 readonly>
        </div>
        <div class="form-group col-md-6">
          <label>Tempat</label>
          <input type="text" class="form-control" value="{{ $rapat->tempat }}" readonly>
        </div>
        <div class="form-group col-md-6">
          <label>Pimpinan Rapat</label>
          <input type="text" class="form-control" value="{{ $rapat->jabatan_pimpinan ?? '-' }}" readonly>
        </div>
        <div class="form-group col-md-12">
          <label>Agenda Rapat</label>
          <input type="text" class="form-control" value="{{ $rapat->deskripsi ?: $rapat->judul }}" readonly>
        </div>
      </div>
    </div>
  </div>

  {{-- FORM --}}
  <form id="form-notulensi" action="{{ route('notulensi.update', $notulensi->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    @if ($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">
          @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
        </ul>
      </div>
    @endif

    {{-- TABEL PEMBAHASAN --}}
    <div class="card">
      <div class="card-header"><b>Pembahasan & Rangkaian Acara</b></div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table mb-0" id="tabel-detail">
            <thead>
              <tr>
                <th style="width:60px">NO</th>
                <th>HASIL MONITORING & EVALUASI / RANGKAIAN ACARA</th>
                <th style="width:25%;">REKOMENDASI TINDAK LANJUT</th>
                <th style="width:16%;">PENANGGUNG JAWAB</th>
                <th style="width:16%;">TGL. PENYELESAIAN</th>
                <th style="width:90px;">AKSI</th>
              </tr>
            </thead>
            <tbody>
            @php $countDetail = count($detail); @endphp

            @forelse($detail as $i => $row)
              <tr>
                <td class="no">{{ $loop->iteration }}</td>
                <td>
                  <textarea name="baris[{{ $i }}][hasil_pembahasan]" class="form-control rich required-rich">{!! $row->hasil_pembahasan !!}</textarea>
                </td>
                <td>
                  <textarea name="baris[{{ $i }}][rekomendasi]" class="form-control rich">{!! $row->rekomendasi !!}</textarea>
                </td>
                <td>
                  <input type="text" name="baris[{{ $i }}][penanggung_jawab]" class="form-control" value="{{ $row->penanggung_jawab }}">
                </td>
                <td>
                  <input type="date" name="baris[{{ $i }}][tgl_penyelesaian]" class="form-control" value="{{ $row->tgl_penyelesaian }}">
                </td>
                <td class="text-center">
                  <button type="button" class="btn btn-danger btn-sm" onclick="hapusBaris(this)">Hapus</button>
                </td>
              </tr>
            @empty
              @php $countDetail = 1; @endphp
              <tr>
                <td class="no">1</td>
                <td><textarea name="baris[0][hasil_pembahasan]" class="form-control rich required-rich"></textarea></td>
                <td><textarea name="baris[0][rekomendasi]" class="form-control rich"></textarea></td>
                <td><input type="text" name="baris[0][penanggung_jawab]" class="form-control" placeholder="Kesekretariatan"></td>
                <td><input type="date" name="baris[0][tgl_penyelesaian]" class="form-control"></td>
                <td class="text-center"><button type="button" class="btn btn-danger btn-sm" onclick="hapusBaris(this)">Hapus</button></td>
              </tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <div class="card-footer text-right">
        <button type="button" id="btn-tambah-baris" class="btn btn-success btn-sm">+ Tambah Baris</button>
      </div>
    </div>

    {{-- DOKUMENTASI --}}
    <div class="card mt-3">
      <div class="card-header"><b>Dokumentasi Kegiatan</b></div>
      <div class="card-body">
        @if(count($dokumentasi))
          <div class="row mb-3">
            @foreach($dokumentasi as $dok)
              <div class="col-md-4 mb-3">
                <div class="panel-soft p-2 h-100">
                  <img src="{{ asset($dok->file_path) }}" class="img-fluid rounded mb-2" alt="dok">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="hapus_dok[]" value="{{ $dok->id }}" id="dok{{ $dok->id }}">
                    <label class="form-check-label" for="dok{{ $dok->id }}">Hapus foto ini</label>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        @else
          <p class="text-muted mb-3">Belum ada dokumentasi.</p>
        @endif

        <div>
          <label>Tambah Foto Baru (boleh lebih dari satu)</label>
          <input type="file" name="dokumentasi_baru[]" class="form-control" accept="image/*" multiple>
          <small class="text-muted">Maks 10MB per file.</small>
        </div>
      </div>
    </div>

    <div class="mt-3">
      <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
      <a href="{{ route('notulensi.show', $notulensi->id) }}" class="btn btn-secondary">Batal</a>
    </div>
  </form>
</div>

{{-- CKEditor 5 --}}
<script src="https://cdn.ckeditor.com/ckeditor5/41.2.0/classic/ckeditor.js"></script>
<script>
(function(){
  let idxBaris = {{ max(1, $countDetail) }};  // index baris berikutnya
  const editors = new Map();

  function initEditors(scope = document){
    scope.querySelectorAll('textarea.rich:not(.ck-ready)').forEach(el => {
      ClassicEditor.create(el, {
        toolbar: [
          'undo','redo','|','bold','italic','underline','|',
          'bulletedList','numberedList','outdent','indent','|',
          'link','insertTable'
        ]
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
      <td class="text-center"><button type="button" class="btn btn-danger btn-sm" onclick="hapusBaris(this)">Hapus</button></td>
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

  // sinkron editor -> textarea & validasi minimal
  document.getElementById('form-notulensi').addEventListener('submit', function(e){
    editors.forEach((editor, el) => { el.value = editor.getData(); });

    let invalid = false;
    this.querySelectorAll('textarea.required-rich').forEach(el => {
      const plain = (editors.get(el)?.getData() || '').replace(/<[^>]*>/g,'').trim();
      if (!plain) {
        invalid = true;
        const cell = el.closest('td');
        if (cell) cell.style.boxShadow = 'inset 0 0 0 2px #dc3545';
      }
    });

    if (invalid){
      e.preventDefault();
      alert('Mohon lengkapi kolom "Hasil Monitoring & Evaluasi / Rangkaian Acara" yang wajib diisi.');
      return false;
    }
  });

  document.addEventListener('DOMContentLoaded', () => initEditors());
})();
</script>
@endsection
