{{-- resources/views/approval/sign.blade.php --}}
@extends('layouts.app')
@section('title','Approval Dokumen')

@section('style')
<style>
  .sheet{background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.02));border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow)}
  .sheet-head{padding:12px 16px;border-bottom:1px solid var(--border);font-weight:700;letter-spacing:.3px;color:#fff}
  .sheet-body{padding:0}
  .info-table td{padding:.65rem .9rem;vertical-align:top;border-top:1px solid var(--border)}
  .info-key{width:28%;font-weight:700;color:#e6eefc;background:rgba(79,70,229,.12);border-right:1px solid var(--border);white-space:nowrap}
  .table.tight td,.table.tight th{padding:.6rem .8rem}
  .alert-block{border-radius:12px;padding:1rem 1.2rem;font-weight:600}
  .alert-warning{background:rgba(245,158,11,.15);color:#f59e0b;border:1px solid rgba(245,158,11,.3)}

  /* Preview panel */
  .preview-wrap{height:72vh; border-radius:12px; overflow:auto; border:1px solid var(--border); background:rgba(255,255,255,.02); position:relative}
  .preview-iframe{width:100%; height:100%; border:0}
  .preview-pages{padding:10px; display:flex; flex-direction:column; gap:12px; align-items:center}
  .preview-page{background:#fff; border-radius:10px; box-shadow:0 8px 18px rgba(0,0,0,.18); overflow:hidden; width:100%}
  .preview-canvas{width:100%; height:auto; display:block}
  .preview-loading{
    position:absolute; inset:0; display:flex; align-items:center; justify-content:center;
    color:var(--muted); font-weight:600; background:rgba(0,0,0,.12);
  }

  /* Aksi approval */
  .act-card{background:linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,.02));border:1px solid var(--border);border-radius:12px}
  .badge-doc{font-weight:800; letter-spacing:.2px}
</style>
@endsection

@section('content')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Approval Dokumen</h3>
    <a href="{{ route('approval.pending') }}" class="btn btn-outline-light btn-sm">
      <i class="fas fa-arrow-left mr-1"></i> Kembali
    </a>
  </div>

  {{-- Flash message --}}
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  {{-- Info dasar --}}
  <div class="sheet mb-3">
    <div class="sheet-head">Informasi Rapat</div>
    <div class="sheet-body">
      <table class="table mb-0 info-table">
        <tbody>
          <tr><td class="info-key">Judul Rapat</td><td>{{ $req->judul }}</td></tr>
          <tr><td class="info-key">Jenis Kegiatan</td><td>{{ $req->nama_kategori ?? '-' }}</td></tr>
          <tr><td class="info-key">Hari/Tanggal/Jam</td><td>{{ \Carbon\Carbon::parse($req->tanggal)->translatedFormat('l, d F Y') }} {{ \App\Helpers\TimeHelper::short($req->waktu_mulai) }}</td></tr>
          <tr><td class="info-key">Tempat</td><td>{{ $req->tempat }}</td></tr>
          <tr>
            <td class="info-key">Jenis Dokumen</td>
            <td><span class="badge badge-info badge-doc">{{ ucfirst($req->doc_type) }}</span></td>
          </tr>
          <tr><td class="info-key">Status</td><td>{{ ucfirst($req->status) }}</td></tr>
          @if(!empty($jumlah_peserta))
            <tr><td class="info-key">Jumlah Peserta</td><td>{{ $jumlah_peserta }} Orang</td></tr>
          @endif
        </tbody>
      </table>
    </div>
  </div>

  @if($blocked)
    <div class="alert-block alert-warning mb-3">
      <i class="fas fa-lock mr-1"></i> Anda belum dapat menandatangani karena tahap sebelumnya belum disetujui.
    </div>
  @endif

  {{-- ==== PREVIEW DOKUMEN (PDF) ==== --}}
  <div class="sheet mb-3">
    <div class="sheet-head d-flex align-items-center">
      <span>Preview Dokumen</span>
      @if(!empty($previewUrl))
        <a href="{{ $previewUrl }}" target="_blank" class="btn btn-sm btn-outline-light ml-auto">
          <i class="fas fa-external-link-alt mr-1"></i> Buka di Tab Baru
        </a>
      @endif
    </div>
    <div class="p-2">
      @if(!empty($previewUrl))
        <div class="preview-wrap" id="pdfPreviewWrap">
          <div class="preview-loading" id="pdfPreviewLoading">Memuat preview...</div>
          <div id="pdfPreviewPages" class="preview-pages"></div>
          <iframe id="pdfPreviewIframe" class="preview-iframe d-none" src="{{ $previewUrl }}"></iframe>
        </div>
      @else
        <div class="text-muted p-3">
          Preview belum tersedia untuk jenis dokumen ini atau route cetak belum didefinisikan.
        </div>
      @endif
    </div>
  </div>

  {{-- ==== Form approval: Approve / Reject ==== --}}
  <form method="POST" action="{{ route('approval.sign.submit', $req->sign_token) }}">
    @csrf

    <div class="act-card p-3">
      <div class="mb-2"><b>Pilih Aksi</b></div>

      <div class="custom-control custom-radio">
        <input type="radio" id="actApprove" name="action" class="custom-control-input" value="approve" checked>
        <label class="custom-control-label" for="actApprove">Setujui &amp; Tanda Tangani</label>
      </div>

      <div class="custom-control custom-radio mt-2">
        <input type="radio" id="actReject" name="action" class="custom-control-input" value="reject">
        <label class="custom-control-label" for="actReject">Tolak (wajib isi catatan)</label>
      </div>

      <div id="rejectNoteWrap" class="mt-3" style="display:none">
        <label for="rejection_note">Catatan Penolakan <span class="text-danger">*</span></label>
        <textarea name="rejection_note" id="rejection_note" class="form-control" rows="3" placeholder="Tuliskan alasan penolakan...">{{ old('rejection_note') }}</textarea>
        @error('rejection_note') <div class="text-danger mt-1">{{ $message }}</div> @enderror
      </div>
    </div>

    <div class="text-right mt-3">
      <a href="{{ route('approval.pending') }}" class="btn btn-secondary">Batal</a>
      <button type="submit" class="btn btn-primary btn-lg" @if($blocked) disabled @endif>
        <i class="fas fa-paper-plane mr-1"></i> Kirim
      </button>
    </div>
  </form>
</div>

@if(!empty($previewUrl))
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
  <script>
  (function(){
    var previewUrl = @json($previewUrl);
    var wrap = document.getElementById('pdfPreviewWrap');
    var pagesEl = document.getElementById('pdfPreviewPages');
    var loading = document.getElementById('pdfPreviewLoading');
    var iframe = document.getElementById('pdfPreviewIframe');
    if (!previewUrl || !wrap || !pagesEl) return;

    function showIframeFallback(msg){
      if (loading) loading.textContent = msg || 'Preview tidak tersedia. Silakan buka di tab baru.';
      if (iframe) iframe.classList.remove('d-none');
    }

    function clearPages(){
      while (pagesEl.firstChild) pagesEl.removeChild(pagesEl.firstChild);
    }

    var renderToken = 0;
    function renderPdf(){
      if (!window.pdfjsLib) { showIframeFallback(); return; }
      window.pdfjsLib.GlobalWorkerOptions.workerSrc =
        'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

      renderToken += 1;
      var token = renderToken;
      clearPages();
      if (loading) loading.style.display = 'flex';

      window.pdfjsLib.getDocument({ url: previewUrl, withCredentials: true }).promise
        .then(async function(pdf){
          var targetWidth = Math.max(320, (wrap.clientWidth || 600) - 20);
          var dpr = window.devicePixelRatio || 1;

          for (var pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
            if (token !== renderToken) return;
            var page = await pdf.getPage(pageNum);
            var viewport = page.getViewport({ scale: 1 });
            var scale = targetWidth / viewport.width;
            var renderViewport = page.getViewport({ scale: scale * dpr });

            var pageWrap = document.createElement('div');
            pageWrap.className = 'preview-page';
            pageWrap.style.maxWidth = targetWidth + 'px';

            var canvas = document.createElement('canvas');
            canvas.className = 'preview-canvas';
            canvas.width = Math.floor(renderViewport.width);
            canvas.height = Math.floor(renderViewport.height);
            canvas.style.width = Math.floor(renderViewport.width / dpr) + 'px';
            canvas.style.height = Math.floor(renderViewport.height / dpr) + 'px';

            pageWrap.appendChild(canvas);
            pagesEl.appendChild(pageWrap);

            var ctx = canvas.getContext('2d');
            await page.render({ canvasContext: ctx, viewport: renderViewport }).promise;
          }

          if (token !== renderToken) return;
          if (loading) loading.style.display = 'none';
        })
        .catch(function(){
          showIframeFallback();
        });
    }

    var resizeTimer;
    window.addEventListener('resize', function(){
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(renderPdf, 180);
    });
    renderPdf();
  })();
  </script>
@endif

<script>
document.addEventListener('DOMContentLoaded', function(){
  const rApprove = document.getElementById('actApprove');
  const rReject  = document.getElementById('actReject');
  const wrap     = document.getElementById('rejectNoteWrap');
  const note     = document.getElementById('rejection_note');

  function sync(){
    if (rReject.checked){
      wrap.style.display = 'block';
      if (note) note.setAttribute('required','required');
    } else {
      wrap.style.display = 'none';
      if (note) {
        note.removeAttribute('required');
        // tidak mengosongkan otomatis agar tidak hilang saat user bolak-balik
      }
    }
  }
  rApprove.addEventListener('change', sync);
  rReject.addEventListener('change', sync);
  sync();
});
</script>
@endsection


