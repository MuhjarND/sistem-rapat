{{-- resources/views/absensi/publik.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Absensi Kehadiran — Publik</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  {{-- ====== Fonts & Base Reset (ringan) ====== --}}
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800&display=swap" rel="stylesheet">

  {{-- ====== Select2 & SignaturePad ====== --}}
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>

  <style>
    :root{
      --bg: #0b1120;
      --panel: #0f172a;
      --panel-2: #0b1229;
      --border: rgba(255,255,255,.12);
      --text: #e5e7eb;
      --muted: #9fb0cd;
      --primary: #6366f1;
      --primary-700:#4f46e5;
      --success:#22c55e;
      --danger:#ef4444;
      --warn:#f59e0b;
      --shadow: 0 12px 30px rgba(0,0,0,.25);
      --radius: 16px;
    }
    *{ box-sizing: border-box; }
    html,body{ height:100%; }
    body{
      margin:0; background:
        radial-gradient(1200px 600px at 10% -20%, rgba(99,102,241,.15), transparent 60%),
        radial-gradient(1000px 700px at 120% 20%, rgba(14,165,233,.12), transparent 50%),
        linear-gradient(180deg, #0a0f1e, #0b1120 40%);
      background-attachment: fixed; /* FIXED BACKGROUND */
      color:var(--text); font-family:Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
    }

    .wrap{ max-width: 980px; margin: 42px auto; padding: 0 16px; }
    .brand{
      display:flex; align-items:center; gap:14px; margin-bottom:14px;
    }
    .brand img{ width:48px; height:48px; object-fit:contain; }
    .brand h1{ margin:0; font-size:1.15rem; font-weight:800; letter-spacing:.3px; }
    .brand .sub{ color:var(--muted); font-weight:600; font-size:.95rem; margin-top:2px; }

    .card{ background: linear-gradient(180deg, rgba(255,255,255,.035), rgba(255,255,255,.02));
           border:1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow); overflow:hidden; }
    .card + .card{ margin-top: 14px; }

    .card-head{ padding:16px 18px; border-bottom:1px solid var(--border);
                display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; }
    .card-head .title{ font-weight:800; letter-spacing:.2px; }
    .card-body{ padding:18px; }

    .grid{ display:grid; gap:14px; grid-template-columns: 1fr; }
    @media(min-width: 720px){
      .grid-2{ grid-template-columns: 1fr 1fr; }
    }

    .meta{
      display:grid; grid-template-columns: 120px 1fr; gap:6px 14px; font-size:.95rem;
    }
    .meta .k{ color:var(--muted); }
    .muted{ color:var(--muted); }

    .stat{
      display:flex; align-items:center; gap:10px; font-weight:700;
      background: rgba(34,197,94,.08); border:1px solid rgba(34,197,94,.18);
      padding:8px 12px; border-radius:12px; width:max-content; margin-top:10px;
    }

    /* ===== Form ===== */
    label{ display:block; margin:0 0 6px; font-weight:700; }
    .form-row{ display:grid; gap:14px; grid-template-columns: 1fr; }
    @media(min-width:720px){ .form-row-2{ grid-template-columns: 1fr 1fr; } }

    .ctl{
      width:100%; height:44px; border-radius:12px; border:1px solid var(--border);
      background: rgba(255,255,255,.055); color:#fff; padding:0 12px; outline:none;
    }
    .ctl:focus{ border-color: rgba(99,102,241,.55); box-shadow: 0 0 0 3px rgba(99,102,241,.18); }

    .select2-container{ width:100%!important; }
    .select2-container--default .select2-selection--single{
      height:44px; border-radius:12px; border:1px solid var(--border);
      background: rgba(255,255,255,.055);
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered{
      color:#fff; line-height:42px; padding-left:12px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow{ height:42px; right:10px; }
    .select2-dropdown{ background: #0b1229; color:#fff; border:1px solid var(--border); }
    .select2-results__option--highlighted[aria-selected]{ background: rgba(99,102,241,.45)!important; }

    .sig-wrap{ border:1px dashed rgba(255,255,255,.25); border-radius:12px; padding:10px;
               background: rgba(255,255,255,.04); }
    canvas#sigPad{ width:100%; height:240px; background: #0b1229; border-radius:10px; border:1px solid var(--border); }

    .btn{
      height:44px; border:none; border-radius:12px; padding:0 16px; font-weight:800; letter-spacing:.2px;
      display:inline-flex; align-items:center; justify-content:center; cursor:pointer;
    }
    .btn-primary{ background: linear-gradient(180deg, var(--primary), var(--primary-700)); color:#fff; }
    .btn-soft{ background: rgba(255,255,255,.06); color:#fff; border:1px solid rgba(255,255,255,.16); }

    .help{ font-size:.85rem; color:var(--muted); margin-top:6px; }
    .alert{ padding:10px 12px; border-radius:12px; margin:12px 0 0; font-weight:600; }
    .alert-success{ background: rgba(34,197,94,.12); border:1px solid rgba(34,197,94,.25); color:#a7f3d0; }
    .alert-danger{ background: rgba(239,68,68,.12); border:1px solid rgba(239,68,68,.27); color:#fecaca; }

    .footer-note{
      margin:18px 0 8px; color:var(--muted); font-size:.9rem; text-align:center;
    }
  </style>
</head>
<body>
  <div class="wrap">

    {{-- Brand --}}
    <div class="brand">
      <img src="{{ asset('logo_qr.png') }}" alt="Logo">
      <div>
        <h1>Sistem Absensi Online PTA Papua Barat</h1>
        <div class="sub">Form Kehadiran BIMTEK PNBP</div>
      </div>
    </div>

    {{-- Flash --}}
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div>   @endif
    @if($errors->any())     <div class="alert alert-danger">{{ $errors->first() }}</div>  @endif

    {{-- ===== Informasi Rapat ===== --}}
    <div class="card">
      <div class="card-head">
        <div class="title">Informasi Rapat</div>
        <div class="stat" title="Total hadir yang sudah tercatat">🔔 <span>Hadir: {{ $hadirCount }}</span></div>
      </div>
      <div class="card-body">
        <div class="grid grid-2">
          <div class="meta">
            <div class="k">Judul</div><div>{{ $rapat->judul }}</div>
            <div class="k">Kategori</div><div>{{ $nama_kat ?? '-' }}</div>
            <div class="k">Tempat</div><div>{{ $rapat->tempat }}</div>
          </div>
          <div class="meta">
            <div class="k">Tanggal</div>
            <div>{{ \Carbon\Carbon::parse($rapat->tanggal)->translatedFormat('l, d F Y') }}</div>
            <div class="k">Waktu</div>
            <div>{{ $rapat->waktu_mulai }} WIT</div>
            <div class="k">Kode Publik</div>
            <div class="muted">{{ $rapat->public_code }}</div>
          </div>
        </div>
      </div>
    </div>

    {{-- ===== Form Absensi ===== --}}
    <div class="card">
      <div class="card-head">
        <div class="title">Form Absensi</div>
        <div class="muted">Pilih nama & tanda tangani</div>
      </div>
      <div class="card-body">
        <form id="formAbsensi" method="POST" action="{{ route('absensi.publik.store', $rapat->public_code) }}" onsubmit="return handleSubmit()">
          @csrf

          <div class="form-row form-row-2">
            {{-- Peserta --}}
            <div>
              <label for="peserta">Nama Anda</label>
              <select id="peserta" name="peserta" required></select>
              <div class="help">
                Cari nama Anda pada daftar Undangan/Tamu. <br class="d-sm-none">
                Jabatan & Instansi otomatis tercetak di laporan.
              </div>
              @error('peserta') <div class="alert alert-danger">{{ $message }}</div> @enderror
            </div>

            {{-- No. HP (opsional) --}}
            <div>
              <label for="no_hp">No. HP (opsional)</label>
              <input type="tel" id="no_hp" name="no_hp" class="ctl" placeholder="08xxxxxxxxxx" value="{{ old('no_hp') }}">
              <div class="help">Jika diisi, Anda akan menerima notifikasi bahwa absensi sudah tercatat.</div>
              @error('no_hp') <div class="alert alert-danger">{{ $message }}</div> @enderror
            </div>
          </div>

          {{-- Tanda tangan --}}
          <div class="form-row" style="margin-top:14px;">
            <div>
              <label>Tanda Tangan</label>
              <div class="sig-wrap">
                <canvas id="sigPad"></canvas>
              </div>
              <div style="margin-top:10px; display:flex; gap:8px;">
                <button type="button" class="btn btn-soft" onclick="clearSig()">Bersihkan</button>
                <button type="button" class="btn btn-soft" onclick="resizeCanvas()">Sesuaikan Kanvas</button>
              </div>
              <input type="hidden" name="ttd" id="ttdInput" required>
              @error('ttd') <div class="alert alert-danger" style="margin-top:10px">{{ $message }}</div> @enderror
            </div>
          </div>

          <div style="margin-top:16px; display:flex; gap:10px; flex-wrap:wrap;">
            <button class="btn btn-primary" id="btnSubmit">
              Kirim Absensi
            </button>
            <div class="help">Dengan menekan tombol, Anda menyetujui pemrosesan data untuk keperluan dokumentasi rapat.</div>
          </div>
        </form>
      </div>
    </div>

    <div class="footer-note">© {{ date('Y') }} PTA Papua Barat • Sistem Absensi Online</div>
  </div>

  {{-- ====== Vendor Scripts ====== --}}
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>

  <script>
    // ===== Select2 Peserta (AJAX) =====
    $(function(){
      $('#peserta').select2({
        width: '100%',
        placeholder: 'Ketik untuk mencari nama…',
        minimumInputLength: 0,
        ajax:{
          url: @json(route('absensi.publik.search', $rapat->public_code)),
          dataType:'json',
          delay: 220,
          data: params => ({ q: params.term || '' }),
          processResults: function(data){
            // pastikan array & paksa id/text menjadi string -> mencegah error .slice()
            const safe = Array.isArray(data) ? data.map(item => ({
              id: String(item.id || ''),
              text: String(item.text || '')
            })) : [];
            return { results: safe };
          },
        },
        language:{
          inputTooShort: () => 'Ketik untuk mencari…',
          searching:      () => 'Mencari…',
          noResults:      () => 'Tidak ditemukan. Hubungi panitia bila nama belum terdaftar.'
        }
      });

      // UX+: buka dropdown saat fokus
      $('#peserta').on('focus', function(){ $(this).select2('open'); });
    });

    // ===== Signature Pad =====
    let sigPad, canvas;
    function resizeCanvas(){
      if(!canvas) return;
      const ratio = Math.max(window.devicePixelRatio || 1, 1);
      const w = canvas.offsetWidth, h = canvas.offsetHeight;
      canvas.width  = w * ratio;
      canvas.height = h * ratio;
      canvas.getContext('2d').scale(ratio, ratio);
      sigPad.clear();
    }
    function clearSig(){ if(sigPad) sigPad.clear(); }

    function normalizePhone(p){
      if(!p) return '';
      p = p.replace(/[^0-9]/g,''); // hanya angka
      return p;
    }

    function handleSubmit(){
      if(!sigPad || sigPad.isEmpty()){
        alert('Mohon tanda tangani terlebih dahulu.');
        return false;
      }
      // simpan TTD
      document.getElementById('ttdInput').value = sigPad.toDataURL('image/png');

      // normalisasi no_hp (opsional)
      const hp = document.getElementById('no_hp');
      if(hp && hp.value){
        hp.value = normalizePhone(hp.value);
        // validasi sederhana: 10-14 digit, diawali 0
        if(!/^0[0-9]{9,13}$/.test(hp.value)){
          alert('Nomor HP tidak valid. Contoh: 081234567890');
          return false;
        }
      }

      // UX: disable submit
      const btn = document.getElementById('btnSubmit');
      btn.disabled = true; btn.textContent = 'Mengirim…';
      return true;
    }

    window.addEventListener('load', function(){
      canvas = document.getElementById('sigPad');
      sigPad = new SignaturePad(canvas, { backgroundColor: 'rgba(255, 255, 255, 1)' });
      // set tinggi default yang nyaman pada mobile
      canvas.style.height = '240px';
      resizeCanvas();
    });
    window.addEventListener('resize', resizeCanvas);
  </script>
</body>
</html>
