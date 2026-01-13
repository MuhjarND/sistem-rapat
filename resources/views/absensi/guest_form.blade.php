﻿{{-- resources/views/absensi/guest_form.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Sistem Absensi Online PTA Papua Barat - {{ $rapat->judul }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  {{-- Bootstrap 4 --}}
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

  <style>
    :root{
      --bg:#101820; --fg:#dbe7ff; --muted:#9fb0cd; --accent:#FEE715; --card:#0e1520;
      --border: rgba(226,232,240,.15);
    }
    body{ background: var(--bg); color: var(--fg); min-height:100vh; }
    .wrap{ min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }
    .cardx{
      width:100%; max-width: 860px; background: rgba(255,255,255,.04);
      border:1px solid var(--border); border-radius:16px; box-shadow: 0 10px 40px rgba(0,0,0,.22);
      overflow:hidden;
    }
    .cardx-header{
      padding:18px 20px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between;
      background: linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.03));
    }
    .title{ margin:0; font-size:18px; font-weight:700; letter-spacing:.3px; }
    .subtitle{ margin:0; font-size:12px; color: var(--muted); }
    .badge-kat{ font-size:12px; background: rgba(254,231,21,.1); color:#fff; border:1px solid rgba(254,231,21,.25); padding:.25rem .5rem; border-radius:999px; }
    .cardx-body{ padding:20px; }
    .form-label{ font-weight:600; color:#e9f1ff; font-size: 13px; margin-bottom:.35rem; }
    .form-control, .custom-select{
      background: rgba(255,255,255,.04); border:1px solid var(--border); color:#fff;
    }
    .form-control:focus, .custom-select:focus{
      border-color: var(--accent); box-shadow: 0 0 0 0.12rem rgba(254,231,21,.25);
      background: rgba(255,255,255,.05); color:#fff;
    }
    .help{ font-size:12px; color: var(--muted); }
    .btn-accent{
      background: var(--accent); color:#101820; font-weight:700; border:none;
      box-shadow: 0 6px 18px rgba(254,231,21,.15);
    }
    .btn-accent:hover{ filter: brightness(1.02); }
    .btn-ghost{
      background: rgba(255,255,255,.06); color:#fff; border:1px solid var(--border);
    }
    .sig-wrap{
      border:1px dashed rgba(226,232,240,.35); background: #fff;
      border-radius:12px; position: relative;
    }
    canvas#signature{
      width:100%; height:260px; display:block; cursor: crosshair; background: #fff;
    }
    .sig-placeholder{
      position:absolute; inset:0; display:flex; align-items:center; justify-content:center;
      color: #6b7280; pointer-events:none; font-size:13px;
    }
    .alert-custom{
      background: rgba(254,231,21,.1); border:1px solid rgba(254,231,21,.35); color:#fff;
    }
    .qr-tamu{
      display:inline-flex; align-items:center; gap:8px; font-size:12px; color: var(--muted);
    }
  </style>
</head>
<body>
<div class="wrap">
  <div class="cardx">
    <div class="cardx-header">
      <div>
        <h5 class="title">Sistem Absensi Online PTA Papua Barat</h5>
        <p class="subtitle mb-0">
          {{ $rapat->judul }} @if(!empty($rapat->nama_kategori)) - <span class="badge-kat">{{ $rapat->nama_kategori }}</span>@endif
        </p>
      </div>
      <div class="text-right">
        <div class="subtitle">
          {{ \Carbon\Carbon::parse($rapat->tanggal)->isoFormat('dddd, D MMMM Y') }} - {{ \App\Helpers\TimeHelper::short($rapat->waktu_mulai) }} WIT
        </div>
        <div class="subtitle">{{ $rapat->tempat }}</div>
      </div>
    </div>

    <div class="cardx-body">
      {{-- Alerts --}}
      @if(session('ok')) <div class="alert alert-success alert-custom">{{ session('ok') }}</div> @endif
      @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
      @if($errors->any())
        <div class="alert alert-danger">
          <strong>Periksa kembali isian Anda:</strong>
          <ul class="mb-0 mt-2">
            @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
          </ul>
        </div>
      @endif

      <form id="guestForm" method="POST" action="{{ route('absensi.guest.submit', [$rapat->id, $token]) }}" onsubmit="return beforeSubmit();">
        @csrf

        <div class="form-row">
          <div class="form-group col-md-6">
            <label class="form-label">Nama<span class="text-warning">*</span></label>
            <input type="text" name="nama" class="form-control" required maxlength="120" value="{{ old('nama') }}">
          </div>
          <div class="form-group col-md-6">
            <label class="form-label">Instansi</label>
            <input type="text" name="instansi" class="form-control" maxlength="150" value="{{ old('instansi') }}">
            <div class="help">Opsional. Contoh: PA Manokwari, K/L/D</div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group col-md-6">
            <label class="form-label">Jabatan</label>
            <input type="text" name="jabatan" class="form-control" maxlength="120" value="{{ old('jabatan') }}">
          </div>
          <div class="form-group col-md-6">
            <label class="form-label">No. HP (WhatsApp)</label>
            <input type="tel" name="no_hp" class="form-control" maxlength="16" value="{{ old('no_hp') }}" placeholder="0812xxxxxxx">
            <div class="help">Opsional. Jika diisi, sistem akan mengirim bukti via WhatsApp.</div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group col-md-4">
            <label class="form-label">Status Kehadiran</label>
            <select name="status" class="custom-select">
              <option value="hadir" {{ old('status')=='hadir' ? 'selected' : '' }}>Hadir</option>
              <option value="izin" {{ old('status')=='izin' ? 'selected' : '' }}>Izin</option>
              <option value="tidak_hadir" {{ old('status')=='tidak_hadir' ? 'selected' : '' }}>Tidak Hadir</option>
            </select>
          </div>
        </div>

        <hr style="border-color: var(--border)">

        <div class="form-group">
          <label class="form-label d-flex align-items-center justify-content-between">
            <span>Tanda Tangan</span>
            <small class="text-muted">Gunakan jari/stylus pada ponsel</small>
          </label>

          <div class="sig-wrap mb-2">
            <canvas id="signature"></canvas>
            <div id="sigPh" class="sig-placeholder">Tanda tangani di area ini...</div>
          </div>
          <div class="d-flex">
            <button type="button" class="btn btn-ghost btn-sm mr-2" onclick="clearSignature()">Bersihkan</button>
            <div class="help">TTD opsional, namun disarankan untuk keabsahan bukti hadir.</div>
          </div>

          {{-- hidden field untuk base64 PNG --}}
          <input type="hidden" name="ttd_data" id="ttd_data">
        </div>

        <div class="d-flex justify-content-between align-items-center mt-4">
          <div class="qr-tamu">
            <span class="badge-kat">Tamu</span>
            <span>Form ini khusus Intansi eksternal.</span>
          </div>
          <button type="submit" class="btn btn-accent px-4">
            Kirim Absensi
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- SignaturePad --}}
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script>
  let canvas = document.getElementById('signature');
  let ph = document.getElementById('sigPh');
  let sigPad;

  function fitCanvas() {
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    const w = canvas.offsetWidth;
    const h = 260; // px tinggi tampilan
    canvas.width  = w * ratio;
    canvas.height = h * ratio;
    canvas.getContext('2d').scale(ratio, ratio);
  }

  function initPad() {
    fitCanvas();
    sigPad = new SignaturePad(canvas, {
      backgroundColor: '#ffffff',
      penColor: '#000000'
    });
    sigPad.addEventListener("beginStroke", () => ph.style.display = 'none');
  }

  function clearSignature(){
    if (!sigPad) return;
    sigPad.clear();
    ph.style.display = 'flex';
    document.getElementById('ttd_data').value = '';
  }

  function beforeSubmit(){
    // kalau ada coretan, simpan base64 ke hidden input
    if (sigPad && !sigPad.isEmpty()) {
      document.getElementById('ttd_data').value = sigPad.toDataURL('image/png');
    }
    // browser akan melanjutkan submit
    return true;
  }

  window.addEventListener('resize', () => {
    // simpan sementara, resize, redraw
    if (!sigPad) return;
    const data = sigPad.toData();
    fitCanvas();
    sigPad.clear();
    sigPad.fromData(data);
    if (sigPad.isEmpty()) ph.style.display = 'flex';
  });

  initPad();
</script>
</body>
</html>
