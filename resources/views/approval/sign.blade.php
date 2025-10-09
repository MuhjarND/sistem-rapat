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
  .preview-wrap{height:72vh; border-radius:12px; overflow:hidden; border:1px solid var(--border); background:rgba(255,255,255,.02)}
  .preview-iframe{width:100%; height:100%; border:0}

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
          <tr><td class="info-key">Hari/Tanggal/Jam</td><td>{{ \Carbon\Carbon::parse($req->tanggal)->translatedFormat('l, d F Y') }} {{ $req->waktu_mulai }}</td></tr>
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

  {{-- Rangkuman pembahasan (khusus notulensi) --}}
  @if($req->doc_type === 'notulensi' && isset($detail) && count($detail))
    <div class="sheet mb-3">
      <div class="sheet-head">Rangkuman Pembahasan</div>
      <div class="sheet-body">
        <table class="table table-bordered table-sm mb-0 tight">
          <thead class="text-center">
            <tr>
              <th style="width:4%">No</th>
              <th>Hasil Pembahasan</th>
              <th style="width:26%">Rekomendasi</th>
              <th style="width:20%">Penanggung Jawab</th>
              <th style="width:14%">Tgl. Penyelesaian</th>
            </tr>
          </thead>
          <tbody>
            @foreach($detail as $i => $d)
              <tr>
                <td class="text-center">{{ $i+1 }}</td>
                <td>{!! $d->hasil_pembahasan !!}</td>
                <td>{!! $d->rekomendasi !!}</td>
                <td>{{ $d->pj_text ?? '-' }}</td>
                <td class="text-center">
                  {{ $d->tgl_penyelesaian ? \Carbon\Carbon::parse($d->tgl_penyelesaian)->translatedFormat('d F Y') : '-' }}
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
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
        <div class="preview-wrap">
          <iframe class="preview-iframe" src="{{ $previewUrl }}"></iframe>
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
