﻿@extends('layouts.app')
@section('title','Edit Notulensi')

@section('style')
<style>
  /* ====== INPUTS & FORM THEME ====== */
  .form-control,.custom-select{
    background: rgba(255,255,255,.06);
    border: 1px solid var(--border);
    color: var(--text);
    border-radius: 10px;
  }
  .form-control:focus,.custom-select:focus{
    background: rgba(255,255,255,.08);
    border-color: rgba(79,70,229,.55);
    box-shadow: 0 0 0 .15rem rgba(79,70,229,.25);
    color: var(--text);
  }
  .form-control[readonly]{ background: rgba(255,255,255,.08)!important; color:var(--text)!important; border-color:var(--border); }
  ::placeholder{ color:#9fb0cd!important; opacity:.75; }

  /* ====== TABLE ====== */
  #tabel-detail thead th{
    background: linear-gradient(180deg, rgba(79,70,229,.22), rgba(14,165,233,.18));
    border-bottom: 1px solid var(--border);
    font-weight: 700; color: var(--text); vertical-align: middle;
  }
  #tabel-detail td, #tabel-detail th{ border-color: var(--border); }
  #tabel-detail .no{ text-align:center; font-weight:700; }

  /* ====== CKEditor DARK ====== */
  .ck.ck-editor{ border-radius:12px; border:1px solid var(--border); background:#0f1533; box-shadow:var(--shadow); }
  .ck.ck-editor .ck.ck-toolbar{ background:linear-gradient(180deg, rgba(17,24,39,.85), rgba(17,24,39,.65)); border-bottom:1px solid var(--border); }
  .ck.ck-editor .ck.ck-toolbar .ck-button{ color:var(--text); }
  .ck.ck-editor .ck.ck-toolbar .ck-button:hover{ background:rgba(79,70,229,.18); color:#fff; border-radius:8px; }
  .ck.ck-editor__editable_inline{ background:#0d1330; color:var(--text); min-height:160px; }
  .ck-content a{ color:#93c5fd; }

  .btn-sm{ border-radius:8px; padding:.25rem .55rem; font-weight:600; }

  .panel-soft{
    background: linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,.02));
    border: 1px solid var(--border);
    border-radius: 14px;
    box-shadow: var(--shadow);
    color: var(--text);
  }

  /* ====== Select2 (dark) ====== */
  .select2-container--default .select2-selection--multiple{
    background:rgba(255,255,255,.06);
    border:1px solid var(--border);
    min-height:38px; border-radius:10px
  }
  .select2-container--default .select2-selection--multiple .select2-selection__choice{
    background:rgba(79,70,229,.25); border:1px solid rgba(79,70,229,.4);
    color:#fff; border-radius:999px
  }
  .select2-dropdown{ background:#0f1533; color:#fff; border:1px solid var(--border) }
  .select2-results__option--highlighted{ background:rgba(79,70,229,.45)!important }

  /* ====== Mobile cards for detail table ====== */
  @media (max-width: 575.98px){
    #tabel-detail thead{ display:none; }
    #tabel-detail tbody tr{
      display:block; border:1px solid var(--border); border-radius:12px;
      margin:10px 12px; overflow:hidden; background:rgba(255,255,255,.02);
    }
    #tabel-detail tbody td{
      display:block; width:100%; border:0!important; border-bottom:1px solid var(--border)!important;
      padding:.7rem .85rem;
    }
    #tabel-detail tbody td:last-child{ border-bottom:0!important; }
    #tabel-detail tbody td[data-label]::before{
      content: attr(data-label);
      display:block; font-size:.72rem; font-weight:800; letter-spacing:.2px;
      color:#9fb0cd; text-transform:uppercase; margin-bottom:6px;
    }
    /* angka "NO" tampil sebagai badge kecil */
    #tabel-detail tbody td.no-badge{
      text-align:left!important;
    }
    #tabel-detail tbody td.no-badge::before{ content:'No'; }
    #tabel-detail tbody td.no-badge > span{
      display:inline-block; min-width:28px; height:22px; padding:0 8px;
      border-radius:999px; background:rgba(99,102,241,.3); font-weight:800; font-size:12px;
      text-align:center; line-height:22px;
    }
  }
</style>
@endsection

@section('content')
@php
  // Siapkan teks Approval 1
  $approval1 = \DB::table('users')
      ->where('id', $rapat->approval1_user_id ?? 0)
      ->select('name','jabatan','unit')
      ->first();
  $approval1_jabatan = $rapat->approval1_jabatan_manual ?: ($approval1->jabatan ?? '-');
  $approval1_text = $approval1
      ? trim(($approval1->name ?? '-') . ' - ' . $approval1_jabatan . (($approval1->unit ?? '') ? ' - '.$approval1->unit : ''))
      : '-';

  // Hitung jumlah peserta rapat
  $jumlah_peserta = \DB::table('undangan')->where('id_rapat', $rapat->id)->count();

  // Pastikan assigneesMap ada (controller sudah kirim)
  $assigneesMap = $assigneesMap ?? []; // [detail_id => [ {id,text}, ... ]]
@endphp

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

  {{-- INFO RAPAT --}}
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
                 value="{{ \Carbon\Carbon::parse($rapat->tanggal)->translatedFormat('l, d F Y') }} {{ \App\Helpers\TimeHelper::short($rapat->waktu_mulai) }}"
                 readonly>
        </div>
        <div class="form-group col-md-6">
          <label>Tempat</label>
          <input type="text" class="form-control" value="{{ $rapat->tempat }}" readonly>
        </div>
        <div class="form-group col-md-6">
          <label>Pemimpin Rapat</label>
          <input type="text" class="form-control" value="{{ $approval1_text }}" readonly>
        </div>
        <div class="form-group col-md-6">
          <label>Jumlah Peserta</label>
          <input type="text" class="form-control" value="{{ $jumlah_peserta }} Orang" readonly>
        </div>
        <div class="form-group col-md-6">
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
                <th style="width:22%;">PENANGGUNG JAWAB (Tag User)</th>
                <th style="width:16%;">TGL. PENYELESAIAN</th>
                <th style="width:90px;">AKSI</th>
              </tr>
            </thead>
            <tbody>
            @php $countDetail = count($detail); @endphp

            @forelse($detail as $i => $row)
              @php $initAssignees = $assigneesMap[$row->id] ?? []; @endphp
              <tr>
                {{-- NO --}}
                <td class="no d-none d-sm-table-cell">{{ $loop->iteration }}</td>
                <td class="no-badge d-sm-none"><span>{{ $loop->iteration }}</span></td>

                {{-- HASIL --}}
                <td data-label="Hasil / Rangkaian">
                  <textarea name="baris[{{ $i }}][hasil_pembahasan]" class="form-control rich required-rich">{!! $row->hasil_pembahasan !!}</textarea>
                </td>

                {{-- REKOMENDASI --}}
                <td data-label="Rekomendasi">
                  <textarea name="baris[{{ $i }}][rekomendasi]" class="form-control rich">{!! $row->rekomendasi !!}</textarea>
                </td>

                {{-- PJ --}}
                <td data-label="Penanggung Jawab">
                  <select name="baris[{{ $i }}][pj_ids][]" class="form-control js-user-tags"
                          multiple data-placeholder="Pilih penanggung jawab"
                          data-initial='@json($initAssignees)'></select>
                  <input type="text" name="baris[{{ $i }}][penanggung_jawab]"
                         class="form-control mt-1"
                         value="{{ $row->penanggung_jawab }}"
                         placeholder="Catatan PJ (opsional)">
                </td>

                {{-- TGL --}}
                <td data-label="Tgl. Penyelesaian">
                  <input type="date" name="baris[{{ $i }}][tgl_penyelesaian]" class="form-control" value="{{ $row->tgl_penyelesaian }}">
                </td>

                {{-- AKSI --}}
                <td class="text-center" data-label="Aksi">
                  <button type="button" class="btn btn-danger btn-sm" onclick="hapusBaris(this)">Hapus</button>
                </td>
              </tr>
            @empty
              @php $countDetail = 1; @endphp
              <tr>
                <td class="no d-none d-sm-table-cell">1</td>
                <td class="no-badge d-sm-none"><span>1</span></td>

                <td data-label="Hasil / Rangkaian"><textarea name="baris[0][hasil_pembahasan]" class="form-control rich required-rich"></textarea></td>
                <td data-label="Rekomendasi"><textarea name="baris[0][rekomendasi]" class="form-control rich"></textarea></td>
                <td data-label="Penanggung Jawab">
                  <select name="baris[0][pj_ids][]" class="form-control js-user-tags" multiple data-placeholder="Pilih penanggung jawab"></select>
                  <input type="text" name="baris[0][penanggung_jawab]" class="form-control mt-1" placeholder="Catatan PJ (opsional)">
                </td>
                <td data-label="Tgl. Penyelesaian"><input type="date" name="baris[0][tgl_penyelesaian]" class="form-control"></td>
                <td class="text-center" data-label="Aksi"><button type="button" class="btn btn-danger btn-sm" onclick="hapusBaris(this)">Hapus</button></td>
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
  let idxBaris = {{ max(1, $countDetail) }};
  const editors = new Map();

  /* ========== CKEditor init ========== */
  function initEditors(scope=document){
    scope.querySelectorAll('textarea.rich:not(.ck-ready)').forEach(el=>{
      ClassicEditor.create(el,{
        toolbar:['undo','redo','|','bold','italic','underline','|','bulletedList','numberedList','outdent','indent','|','link','insertTable']
      }).then(ed=>{
        editors.set(el, ed);
        el.classList.add('ck-ready');
      }).catch(console.error);
    });
  }
  function destroyEditorsInside(node){
    node.querySelectorAll('textarea.rich.ck-ready').forEach(el=>{
      const inst = editors.get(el);
      if(inst){ inst.destroy().catch(()=>{}); editors.delete(el); }
      el.classList.remove('ck-ready');
    });
  }

  /* ========== Select2 (AJAX tag user) ========== */
  const USER_SEARCH_URL = @json(route('users.search'));
  function initUserTags(scope=document){
    $(scope).find('.js-user-tags').each(function(){
      const $el = $(this);
      $el.select2({
        width:'100%',
        ajax:{
          url: USER_SEARCH_URL,
          dataType:'json',
          delay:220,
          cache:true,
          data: params => ({ q: params.term || '' }),
          processResults: data => ({ results: data }),
          transport: function (params, success, failure) {
            const req = $.ajax(params);
            req.then(success);
            req.fail(function(xhr){
              if (xhr.status === 401) { alert('Sesi berakhir. Silakan login ulang.'); location.reload(); return failure(); }
              if (xhr.status === 419) { alert('Sesi kedaluwarsa (419). Muat ulang halaman.'); return failure(); }
              failure();
            });
            return req;
          }
        }
      });

      // preload dari data-initial
      const init = $el.data('initial') || [];
      if (Array.isArray(init) && init.length){
        init.forEach(function(opt){
          if(!$el.find('option[value="'+opt.id+'"]').length){
            $el.append(new Option(opt.text || ('User #'+opt.id), opt.id, true, true));
          }
        });
        $el.trigger('change');
      }
    });
  }

  /* ========== Helpers tabel ========== */
  function renumber(){
    const rows = document.querySelectorAll('#tabel-detail tbody tr');
    rows.forEach((tr,i)=>{
      const noCell = tr.querySelector('.no');
      if(noCell) noCell.textContent = i+1;
      tr.querySelectorAll('textarea,input,select').forEach(inp=>{
        if(!inp.name) return;
        inp.name = inp.name.replace(/baris\[\d+\]/, 'baris['+i+']');
      });
      const badge = tr.querySelector('.no-badge span');
      if(badge) badge.textContent = i+1;
    });
    idxBaris = rows.length;
  }

  document.getElementById('btn-tambah-baris').addEventListener('click',()=>{
    const tbody=document.querySelector('#tabel-detail tbody');
    const tr=document.createElement('tr');
    tr.innerHTML=`
      <td class="no d-none d-sm-table-cell"></td>
      <td class="no-badge d-sm-none"><span></span></td>
      <td data-label="Hasil / Rangkaian"><textarea name="baris[${idxBaris}][hasil_pembahasan]" class="form-control rich required-rich"></textarea></td>
      <td data-label="Rekomendasi"><textarea name="baris[${idxBaris}][rekomendasi]" class="form-control rich"></textarea></td>
      <td data-label="Penanggung Jawab">
        <select name="baris[${idxBaris}][pj_ids][]" class="form-control js-user-tags" multiple data-placeholder="Pilih penanggung jawab"></select>
        <input type="text" name="baris[${idxBaris}][penanggung_jawab]" class="form-control mt-1" placeholder="Catatan PJ (opsional)">
      </td>
      <td data-label="Tgl. Penyelesaian"><input type="date" name="baris[${idxBaris}][tgl_penyelesaian]" class="form-control"></td>
      <td class="text-center" data-label="Aksi"><button type="button" class="btn btn-danger btn-sm" onclick="hapusBaris(this)">Hapus</button></td>`;
    tbody.appendChild(tr);
    initEditors(tr);
    initUserTags(tr);
    renumber();
  });

  window.hapusBaris = function(btn){
    const tr = btn.closest('tr');
    destroyEditorsInside(tr);
    tr.remove();
    renumber();
  }

  // Submit: commit CKEditor data + validasi basic
  document.getElementById('form-notulensi').addEventListener('submit',function(e){
    editors.forEach((ed,el)=>{ el.value = ed.getData(); });
    let invalid=false;
    this.querySelectorAll('textarea.required-rich').forEach(el=>{
      const plain=(editors.get(el)?.getData()||'').replace(/<[^>]*>/g,'').trim();
      if(!plain){
        invalid=true;
        const cell=el.closest('td'); if(cell) cell.style.boxShadow='inset 0 0 0 2px #dc3545';
      }
    });
    if(invalid){
      e.preventDefault();
      alert('Mohon lengkapi kolom "Hasil Monitoring & Evaluasi / Rangkaian Acara".');
    }
  });

  // init
  document.addEventListener('DOMContentLoaded',()=>{
    initEditors();
    initUserTags(document);
  });
})();
</script>
@endsection


