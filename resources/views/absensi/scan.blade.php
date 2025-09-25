@extends('layouts.app')
@section('title','Konfirmasi Kehadiran')

@push('style')
<style>
  .sig-wrap{background:#0b1026;border:1px dashed rgba(255,255,255,.25);border-radius:12px;padding:12px}
  .sig-canvas{width:100%;height:280px;background:#fff;border-radius:10px;touch-action:none;display:block}
  .sig-toolbar .btn{margin-right:8px;margin-bottom:8px}
  .hint{color:var(--muted);font-size:.9rem}
</style>
@endpush

@section('content')
<div class="card">
  <div class="card-header d-flex align-items-center">
    <strong>Konfirmasi Kehadiran</strong>

    {{-- Tombol navigasi yang jelas --}}
    <div class="ml-auto d-flex">
      {{-- 1) Kembali ke halaman sebelumnya (fallback) --}}
      <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-light mr-2">
        <i class="fas fa-arrow-left mr-1"></i> Kembali
      </a>
      {{-- 2) Ke Dashboard Peserta --}}
      <a href="{{ route('peserta.dashboard') }}" class="btn btn-sm btn-outline-light mr-2">
        <i class="fas fa-home mr-1"></i> Dashboard
      </a>
      {{-- 3) Ke Daftar Rapat Peserta --}}
      <a href="{{ route('peserta.rapat') }}" class="btn btn-sm btn-outline-light">
        <i class="fas fa-calendar-alt mr-1"></i> Daftar Rapat
      </a>
    </div>
  </div>

  <div class="card-body">
    <h4 class="mb-1">{{ $rapat->judul }}</h4>
    <div class="text-muted mb-2">
      {{ \Carbon\Carbon::parse($rapat->tanggal)->isoFormat('dddd, D MMMM Y') }}
      • {{ $rapat->waktu_mulai }} WIT • {{ $rapat->tempat }}
    </div>

    {{-- Info jendela absensi (jika variabel dikirim dari controller) --}}
    @isset($abs_start, $abs_end, $abs_open)
      <div class="mb-3">
        @if($abs_open)
          <span class="badge badge-success">Jendela absensi sedang DIBUKA</span>
        @else
          <span class="badge badge-secondary">Jendela absensi TERTUTUP</span>
        @endif
        <div class="hint mt-1">
          Rentang: <b>{{ $abs_start->isoFormat('D MMM Y HH:mm') }}</b> s/d
          <b>{{ $abs_end->isoFormat('D MMM Y HH:mm') }}</b> (WIT).
        </div>
      </div>
    @endisset

    @if(!empty($sudah_absen) && $sudah_absen===true)
      <div class="alert alert-success mb-0">
        Anda sudah tercatat <b>hadir</b> pada rapat ini. Terima kasih. 
      </div>
    @else
      @if(isset($abs_open) && !$abs_open)
        <div class="alert alert-warning">
          Absensi belum/bukan waktunya. Silakan kembali pada rentang di atas.
        </div>
      @endif

      <p class="hint mb-2">
        Silakan tanda tangani di area berikut (gunakan mouse / jari). Pastikan tanda tangan jelas, lalu klik <b>Simpan</b>.
      </p>

      <div class="sig-wrap mb-2">
        <canvas id="sigCanvas" class="sig-canvas"></canvas>
      </div>

      <div class="sig-toolbar mb-3">
        <button id="btnUndo"  type="button" class="btn btn-outline-light btn-sm"><i class="fas fa-undo mr-1"></i>Undo</button>
        <button id="btnClear" type="button" class="btn btn-outline-light btn-sm"><i class="fas fa-eraser mr-1"></i>Bersihkan</button>
      </div>

      <form method="POST" action="{{ route('absensi.scan.save', $rapat->token_qr) }}" onsubmit="return beforeSubmit()">
        @csrf
        <input type="hidden" name="signature_data" id="signature_data">
        <input type="hidden" name="ua" value="{{ request()->header('User-Agent') }}">
        <input type="hidden" name="tz" id="tz">
        <button type="submit" id="btnSubmit" class="btn btn-primary" disabled>
          <i class="fas fa-save mr-1"></i> Simpan Kehadiran
        </button>
        <span id="sigEmptyWarn" class="text-warning ml-2" style="display:none">* Tanda tangan masih kosong.</span>
      </form>

      <p class="hint mt-3 mb-0">
        Dengan menekan Simpan, Anda menyetujui perekaman tanda tangan digital untuk keperluan kehadiran rapat.
      </p>
    @endif
  </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  const canvas = document.getElementById('sigCanvas');
  const ctx    = canvas.getContext('2d');
  const submitBtn = document.getElementById('btnSubmit');
  const sigInput  = document.getElementById('signature_data');
  const warnEl    = document.getElementById('sigEmptyWarn');
  const tzInput   = document.getElementById('tz');
  tzInput.value = Intl.DateTimeFormat().resolvedOptions().timeZone || '';

  // Nonaktifkan interaksi jika jendela absensi tertutup
  const absOpen = {{ isset($abs_open) && $abs_open ? 'true' : 'false' }};
  const already = {{ !empty($sudah_absen) && $sudah_absen===true ? 'true' : 'false' }};

  // Konfigurasi ketebalan
  const PEN = {
    displayWidth: 3.2,
    exportWidth : 3.6,
    dotRadius   : 1.6
  };

  function resizeCanvas(){
    const dpr = Math.max(window.devicePixelRatio || 1, 2);
    const rect = canvas.getBoundingClientRect();
    canvas.width  = Math.floor(rect.width * dpr);
    canvas.height = Math.floor(rect.height * dpr);

    ctx.setTransform(1,0,0,1,0,0);
    ctx.scale(dpr, dpr);

    ctx.lineWidth   = PEN.displayWidth;
    ctx.lineCap     = 'round';
    ctx.lineJoin    = 'round';
    ctx.strokeStyle = '#000';

    redraw();
  }
  window.addEventListener('resize', resizeCanvas);

  let drawing = false;
  let paths = [];
  let curr  = [];

  function getPos(e){
    const rect = canvas.getBoundingClientRect();
    const isTouch = e.touches && e.touches.length;
    const clientX = isTouch ? e.touches[0].clientX : e.clientX;
    const clientY = isTouch ? e.touches[0].clientY : e.clientY;
    return { x: clientX - rect.left, y: clientY - rect.top };
  }

  function start(e){
    if(!absOpen || already) return;
    e.preventDefault();
    drawing = true;
    curr = [];
    curr.push(getPos(e));
    redraw();
  }
  function move(e){
    if(!drawing) return;
    e.preventDefault();
    curr.push(getPos(e));
    redraw();
  }
  function end(e){
    if(!drawing) return;
    drawing = false;
    if(curr.length>0){ paths.push(curr); curr = []; }
    redraw();
  }

  function drawLine(ctxLocal, arr, width, dotRadius){
    ctxLocal.lineWidth = width;
    if(arr.length<2){
      const p = arr[0];
      ctxLocal.beginPath();
      ctxLocal.arc(p.x, p.y, dotRadius, 0, Math.PI*2);
      ctxLocal.fillStyle = '#000';
      ctxLocal.fill();
      return;
    }
    ctxLocal.beginPath();
    ctxLocal.moveTo(arr[0].x, arr[0].y);
    for(let i=1;i<arr.length;i++){
      ctxLocal.lineTo(arr[i].x, arr[i].y);
    }
    ctxLocal.stroke();
  }

  function redraw(){
    const rect = canvas.getBoundingClientRect();
    ctx.clearRect(0,0,rect.width,rect.height);

    paths.forEach(arr => drawLine(ctx, arr, PEN.displayWidth, PEN.dotRadius));
    if(curr.length) drawLine(ctx, curr, PEN.displayWidth, PEN.dotRadius);

    const hasInk = paths.length>0 || curr.length>0;
    const enable = hasInk && absOpen && !already;
    if (submitBtn) submitBtn.disabled = !enable;
    if (warnEl) warnEl.style.display = hasInk ? 'none' : 'inline';

    // Visual disable saat tertutup/sudah absen
    if (!absOpen || already) {
      canvas.style.opacity = 0.6;
      canvas.style.pointerEvents = 'none';
      const undo = document.getElementById('btnUndo');
      const clr  = document.getElementById('btnClear');
      if (undo) undo.disabled = true;
      if (clr)  clr.disabled  = true;
      if (submitBtn) submitBtn.disabled = true;
    }
  }

  // events
  canvas.addEventListener('mousedown', start);
  canvas.addEventListener('mousemove', move);
  canvas.addEventListener('mouseup',   end);
  canvas.addEventListener('mouseleave',end);
  canvas.addEventListener('touchstart', start, {passive:false});
  canvas.addEventListener('touchmove',  move,  {passive:false});
  canvas.addEventListener('touchend',   end);

  // toolbar
  const btnClear = document.getElementById('btnClear');
  const btnUndo  = document.getElementById('btnUndo');
  if (btnClear) btnClear.addEventListener('click', function(){ if(!absOpen||already) return; paths = []; curr = []; redraw(); });
  if (btnUndo)  btnUndo.addEventListener('click',  function(){ if(!absOpen||already) return; paths.pop(); redraw(); });

  // init
  resizeCanvas();

  // submit hook
  window.beforeSubmit = function(){
    if(!absOpen || already) return false;
    if(paths.length===0){ redraw(); return false; }

    const rect = canvas.getBoundingClientRect();
    const exportW = Math.max(600, Math.floor(rect.width));
    const exportH = Math.max(260, Math.floor(rect.height));

    const tmp = document.createElement('canvas');
    tmp.width = exportW;
    tmp.height= exportH;

    const tctx = tmp.getContext('2d');
    tctx.fillStyle = '#fff';
    tctx.fillRect(0,0,tmp.width,tmp.height);
    tctx.lineCap = 'round';
    tctx.lineJoin= 'round';
    tctx.strokeStyle = '#000';

    const sx = exportW/rect.width, sy = exportH/rect.height;

    function drawScaled(arr, width, dotRadius){
      if(arr.length<2){
        const p = arr[0];
        tctx.beginPath();
        tctx.arc(p.x*sx, p.y*sy, dotRadius, 0, Math.PI*2);
        tctx.fillStyle='#000'; tctx.fill(); return;
      }
      tctx.beginPath();
      tctx.moveTo(arr[0].x*sx, arr[0].y*sy);
      for(let i=1;i<arr.length;i++){
        tctx.lineTo(arr[i].x*sx, arr[i].y*sy);
      }
      tctx.lineWidth = width;
      tctx.stroke();
    }

    paths.forEach(arr => drawScaled(arr, PEN.exportWidth, PEN.dotRadius));

    tctx.globalAlpha = 0.6;
    const boldWidth = PEN.exportWidth + 0.8;
    paths.forEach(arr => drawScaled(arr, boldWidth, PEN.dotRadius));
    tctx.globalAlpha = 1;

    const dataUrl = tmp.toDataURL('image/png');
    sigInput.value = dataUrl;

    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerText = 'Menyimpan...';
    }
    return true;
  };
})();
</script>
@endpush
