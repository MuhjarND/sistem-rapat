@extends('layouts.app')
@section('title','Konfirmasi Kehadiran')

@push('style')
<style>
  .sig-wrap{background:#0b1026;border:1px dashed rgba(255,255,255,.25);border-radius:12px;padding:12px}
  .sig-canvas{width:100%;height:280px;background:#fff;border-radius:10px;touch-action:none;display:block}
  .sig-toolbar .btn{margin-right:8px;margin-bottom:8px}
  .hint{color:var(--muted);font-size:.9rem}
  .countdown{font-weight:700; letter-spacing:.3px}
</style>
@endpush

@section('content')
<div class="card">
  <div class="card-header d-flex align-items-center">
    <strong>Konfirmasi Kehadiran</strong>

    <div class="ml-auto d-flex">
      <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-light mr-2">
        <i class="fas fa-arrow-left mr-1"></i> Kembali
      </a>
      <a href="{{ route('peserta.dashboard') }}" class="btn btn-sm btn-outline-light mr-2">
        <i class="fas fa-home mr-1"></i> Dashboard
      </a>
      <a href="{{ route('peserta.rapat') }}" class="btn btn-sm btn-outline-light">
        <i class="fas fa-calendar-alt mr-1"></i> Daftar Rapat
      </a>
    </div>
  </div>

  <div class="card-body">
    {{-- Flash message --}}
    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
      <div class="alert alert-danger mb-2">
        @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
      </div>
    @endif

    <h4 class="mb-1">{{ $rapat->judul }}</h4>
    <div class="text-muted mb-2">
      {{ \Carbon\Carbon::parse($rapat->tanggal)->isoFormat('dddd, D MMMM Y') }}
      â€¢ {{ \App\Helpers\TimeHelper::short($rapat->waktu_mulai) }} WIT â€¢ {{ $rapat->tempat }}
    </div>

    {{-- Info jendela absensi --}}
    @isset($abs_start, $abs_end, $abs_open, $abs_before, $abs_after)
      <div class="mb-3">
        @if(!empty($abs_unlimited))
          <span class="badge badge-success">Absensi selalu dibuka</span>
          <div class="hint mt-1">
            Tidak ada batas waktu untuk melakukan absensi.
          </div>
        @elseif($abs_open)
          <span class="badge badge-success">Jendela absensi SEDANG DIBUKA</span>
          <div class="hint mt-1">
            Tutup pada: <b>{{ $abs_end->isoFormat('D MMM Y HH:mm') }}</b> (WIT).
          </div>
        @elseif($abs_before)
          <span class="badge badge-info">Belum Dibuka</span>
          <div class="hint mt-1">
            Dibuka pada: <b>{{ $abs_start->isoFormat('D MMM Y HH:mm') }}</b> (WIT)
            â€” <span id="cdOpen" class="countdown" 
                    data-start="{{ $abs_start->toIso8601String() }}"></span>
          </div>
        @elseif($abs_after)
          <span class="badge badge-secondary">Sudah Ditutup</span>
          <div class="hint mt-1">
            Ditutup pada: <b>{{ $abs_end->isoFormat('D MMM Y HH:mm') }}</b> (WIT).
          </div>
        @endif
      </div>
    @endisset

    {{-- Sudah absen? --}}
    @if(!empty($sudah_absen) && $sudah_absen===true)
      <div class="alert alert-success mb-0">
        Anda sudah tercatat <b>hadir</b> pada rapat ini. Terima kasih.
      </div>
    @else
      {{-- Jika jendela tertutup / belum dibuka, tampilkan peringatan jelas --}}
      @if(empty($abs_unlimited) && isset($abs_before) && $abs_before)
        <div class="alert alert-warning">
          Absensi <b>belum dibuka</b>. Silakan kembali saat waktu yang ditentukan.
        </div>
      @elseif(empty($abs_unlimited) && isset($abs_after) && $abs_after)
        <div class="alert alert-danger">
          Absensi <b>sudah ditutup</b>. Pengisian tanda tangan tidak tersedia.
        </div>
      @endif

      {{-- Form tanda tangan --}}
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
  const canvas   = document.getElementById('sigCanvas');
  const ctx      = canvas.getContext('2d');
  const submitBtn= document.getElementById('btnSubmit');
  const sigInput = document.getElementById('signature_data');
  const warnEl   = document.getElementById('sigEmptyWarn');
  const tzInput  = document.getElementById('tz');
  tzInput.value  = Intl.DateTimeFormat().resolvedOptions().timeZone || '';

  // Status dari server
  const absUnlimited = {{ !empty($abs_unlimited) ? 'true' : 'false' }};
  const absOpen  = absUnlimited ? true : {{ isset($abs_open)  && $abs_open  ? 'true' : 'false' }};
  const absBefore= absUnlimited ? false : {{ isset($abs_before)&& $abs_before? 'true' : 'false' }};
  const absAfter = absUnlimited ? false : {{ isset($abs_after) && $abs_after ? 'true' : 'false' }};
  const already  = {{ !empty($sudah_absen) && $sudah_absen===true ? 'true' : 'false' }};

  // Countdown "dibuka dalam ..."
  (function initCountdown(){
    if (!absBefore) return;
    const el = document.getElementById('cdOpen');
    if (!el) return;
    const startIso = el.getAttribute('data-start');
    const startAt  = startIso ? new Date(startIso) : null;
    if (!startAt) return;

    function pad(n){ return (n<10?'0':'')+n; }
    function tick(){
      const now = new Date();
      let diff = Math.max(0, startAt - now);
      const d = Math.floor(diff / (24*3600*1000)); diff -= d*24*3600*1000;
      const h = Math.floor(diff / (3600*1000));    diff -= h*3600*1000;
      const m = Math.floor(diff / (60*1000));      diff -= m*60*1000;
      const s = Math.floor(diff / 1000);
      el.textContent = d>0 ? `${d}h ${pad(h)}:${pad(m)}:${pad(s)}` : `${pad(h)}:${pad(m)}:${pad(s)}`;
      if (startAt - now <= 0) location.reload();
    }
    tick();
    setInterval(tick, 1000);
  })();

  // Pena
  const PEN = { displayWidth: 3.2, exportWidth: 3.6, dotRadius: 1.6 };

  function resizeCanvas(){
    const dpr  = Math.max(window.devicePixelRatio || 1, 2);
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

  let drawing = false, paths = [], curr = [];
  function getPos(e){
    const rect = canvas.getBoundingClientRect();
    const t    = e.touches && e.touches.length;
    const x    = t ? e.touches[0].clientX : e.clientX;
    const y    = t ? e.touches[0].clientY : e.clientY;
    return { x: x - rect.left, y: y - rect.top };
  }

  function start(e){ if(!absOpen || already){return;} e.preventDefault(); drawing=true; curr=[]; curr.push(getPos(e)); redraw(); }
  function move(e){ if(!drawing) return; e.preventDefault(); curr.push(getPos(e)); redraw(); }
  function end(e){  if(!drawing) return; drawing=false; if(curr.length>0){ paths.push(curr); curr=[]; } redraw(); }

  function drawLine(ctxLocal, arr, width, dotRadius){
    ctxLocal.lineWidth = width;
    if(arr.length<2){
      const p = arr[0]; ctxLocal.beginPath(); ctxLocal.arc(p.x, p.y, dotRadius, 0, Math.PI*2); ctxLocal.fillStyle='#000'; ctxLocal.fill(); return;
    }
    ctxLocal.beginPath(); ctxLocal.moveTo(arr[0].x, arr[0].y);
    for(let i=1;i<arr.length;i++){ ctxLocal.lineTo(arr[i].x, arr[i].y); }
    ctxLocal.stroke();
  }

  function redraw(){
    const rect = canvas.getBoundingClientRect();
    ctx.clearRect(0,0,rect.width,rect.height);
    paths.forEach(a => drawLine(ctx, a, PEN.displayWidth, PEN.dotRadius));
    if(curr.length) drawLine(ctx, curr, PEN.displayWidth, PEN.dotRadius);

    const hasInk = paths.length>0 || curr.length>0;
    const enable = hasInk && absOpen && !already;
    if (submitBtn) submitBtn.disabled = !enable;
    if (warnEl)    warnEl.style.display = hasInk ? 'none' : 'inline';

    if (!absOpen || already){
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
  if (btnClear) btnClear.addEventListener('click', function(){ if(!absOpen||already) return; paths=[]; curr=[]; redraw(); });
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
    tmp.width = exportW; tmp.height = exportH;

    const tctx = tmp.getContext('2d');
    tctx.fillStyle = '#fff'; tctx.fillRect(0,0,tmp.width,tmp.height);
    tctx.lineCap='round'; tctx.lineJoin='round'; tctx.strokeStyle='#000';

    const sx = exportW/rect.width, sy = exportH/rect.height;

    function drawScaled(arr, width, dotRadius){
      if(arr.length<2){
        const p = arr[0]; tctx.beginPath(); tctx.arc(p.x*sx, p.y*sy, dotRadius, 0, Math.PI*2); tctx.fillStyle='#000'; tctx.fill(); return;
      }
      tctx.beginPath(); tctx.moveTo(arr[0].x*sx, arr[0].y*sy);
      for(let i=1;i<arr.length;i++){ tctx.lineTo(arr[i].x*sx, arr[i].y*sy); }
      tctx.lineWidth = width; tctx.stroke();
    }

    paths.forEach(a => drawScaled(a, PEN.exportWidth, PEN.dotRadius));
    tctx.globalAlpha = 0.6;
    paths.forEach(a => drawScaled(a, PEN.exportWidth+0.8, PEN.dotRadius));
    tctx.globalAlpha = 1;

    sigInput.value = tmp.toDataURL('image/png');
    if (submitBtn){ submitBtn.disabled = true; submitBtn.innerText = 'Menyimpan...'; }
    return true;
  };
})();
</script>
@endpush


