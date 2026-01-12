@extends('layouts.app') 
@section('title','Detail Rapat')

@push('style')
<style>
  /* ===== Surat (fallback) ===== */
  .letter{
    background:#fff;color:#111;border-radius:10px;border:1px solid #e5e7eb;
    padding:22px 24px;font-family:"Times New Roman", Times, serif;
  }
  .letter h3{ margin:0 0 6px 0; font-weight:700; text-align:center; }
  .letter .meta{ font-size:12pt; margin-bottom:10px; }
  .letter table{ width:100%; border-collapse:collapse; font-size:11pt; }
  .letter .meta td{ padding:3px 0; vertical-align:top; }
  .letter .divider{ height:1px; background:#e5e7eb; margin:12px 0; }
  .letter ol{ margin:8px 0 0 18px; padding:0; }
  .letter li{ margin:2px 0; }
  .muted{ color:#64748b; }

  /* ===== PDF preview ===== */
  .pdf-wrap{
    border:1px solid var(--border);
    border-radius:12px;
    background:linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.02));
    overflow:hidden;
  }
  .pdf-frame{
    position:relative; width:100%; height:70vh;
    background:#111827;
  }
  .pdf-frame iframe{
    width:100%; height:100%; border:0; display:block; background:#1f2937;
  }
  @media (max-width: 991.98px){
    .pdf-frame{ height:65vh; }
  }
</style>
@endpush

@section('content')
<div class="card mb-3">
  <div class="card-header d-flex align-items-center">
    <strong>Detail Rapat</strong>
    <div class="ml-auto">
      <a href="{{ route('peserta.rapat') }}" class="btn btn-sm btn-outline-light">
        <i class="fas fa-list mr-1"></i> Daftar Rapat
      </a>
      <a href="{{ route('peserta.dashboard') }}" class="btn btn-sm btn-outline-light">
        <i class="fas fa-home mr-1"></i> Dashboard
      </a>
    </div>
  </div>
  <div class="card-body">
    {{-- Info rapat ringkas --}}
    <h4 class="mb-1">{{ $rapat->judul }}</h4>
    <div class="text-muted mb-3">
      {{ \Carbon\Carbon::parse($rapat->tanggal)->isoFormat('dddd, D MMMM Y') }}
      â€¢ {{ \App\Helpers\TimeHelper::short($rapat->waktu_mulai) }} WIT â€¢ {{ $rapat->tempat }}
    </div>
    <div class="row">
      <div class="col-md-6">
        <p class="mb-1"><b>Kategori:</b> {{ $rapat->nama_kategori ?? '-' }}</p>
        <p class="mb-1"><b>Nomor Undangan:</b> {{ $rapat->nomor_undangan ?? 'â€”' }}</p>
      </div>
      <div class="col-md-6">
        <p class="mb-1"><b>Pimpinan Rapat:</b> {{ $rapat->nama_pimpinan ?? '-' }}</p>
        <p class="mb-1"><b>Jabatan:</b> {{ $rapat->jabatan_pimpinan ?? '-' }}</p>
      </div>
    </div>

    {{-- Aksi cepat: Konfirmasi Absensi / Notulensi --}}
    <div class="mt-3">
      @if(!empty($rapat->token_qr))
        <a href="{{ route('absensi.scan', $rapat->token_qr) }}" class="btn btn-primary btn-sm">
          <i class="fas fa-pen-nib mr-1"></i> Konfirmasi / Isi Absensi
        </a>
      @else
        <a href="{{ route('peserta.absensi', $rapat->id) }}" class="btn btn-primary btn-sm">
          <i class="fas fa-pen-nib mr-1"></i> Konfirmasi / Isi Absensi
        </a>
      @endif

      @if(!empty($notulensi_id))
        <a href="{{ route('peserta.notulensi.show', $rapat->id) }}" class="btn btn-info btn-sm ml-2">
          <i class="fas fa-book-open mr-1"></i> Lihat Notulensi
        </a>
      @endif
    </div>
  </div>
</div>

{{-- ================== PREVIEW UNDANGAN ================== --}}
<div class="card">
  <div class="card-header d-flex align-items-center">
    <strong>Preview Undangan</strong>

    @if(!empty($undangan_pdf_url))
      <a href="{{ $undangan_pdf_url }}?inline=1" target="_blank" class="btn btn-sm btn-outline-light ml-auto">
        <i class="fas fa-file-pdf mr-1"></i> Buka / Unduh PDF
      </a>
    @endif
  </div>

  <div class="card-body">
    @if(!empty($undangan_pdf_url))
      {{-- PDF via controller undanganPdf (harus stream inline) --}}
      <div class="pdf-wrap">
        <div class="pdf-frame">
          <iframe
            src="{{ $undangan_pdf_url }}?inline=1#view=FitH"
            title="Undangan Rapat"
            allow="fullscreen"
          ></iframe>
        </div>
      </div>
      <div class="text-muted mt-2" style="font-size:.9rem">
        Jika PDF tidak tampil (dibatasi browser), klik tombol <b>Buka / Unduh PDF</b> di kanan.
      </div>
    @else
      {{-- Fallback: surat HTML --}}
      <div class="letter">
        <h3>UNDANGAN RAPAT</h3>
        <div class="meta">
          <table>
            <tr>
              <td width="28%">Nomor</td><td width="2%">:</td>
              <td>{{ $rapat->nomor_undangan ?? 'â€”' }}</td>
            </tr>
            <tr>
              <td>Perihal</td><td>:</td>
              <td>{{ $rapat->judul }}</td>
            </tr>
            <tr>
              <td>Hari/Tanggal</td><td>:</td>
              <td>{{ \Carbon\Carbon::parse($rapat->tanggal)->isoFormat('dddd, D MMMM Y') }}</td>
            </tr>
            <tr>
              <td>Waktu</td><td>:</td>
              <td>{{ \App\Helpers\TimeHelper::short($rapat->waktu_mulai) }} WIT s/d selesai</td>
            </tr>
            <tr>
              <td>Tempat</td><td>:</td>
              <td>{{ $rapat->tempat }}</td>
            </tr>
            <tr>
              <td>Kategori</td><td>:</td>
              <td>{{ $rapat->nama_kategori ?? '-' }}</td>
            </tr>
            <tr>
              <td>Pimpinan Rapat</td><td>:</td>
              <td>
                {{ $rapat->nama_pimpinan ?? '-' }}
                @if(!empty($rapat->jabatan_pimpinan))
                  <span class="muted">({{ $rapat->jabatan_pimpinan }})</span>
                @endif
              </td>
            </tr>
          </table>
        </div>

        <div class="divider"></div>

        <p class="mb-1"><b>Daftar Penerima Undangan:</b></p>
        @if(($penerima->count() ?? 0) > 0)
          <ol>
            @foreach($penerima as $u)
              <li>
                {{ $u->name }}
                @if(!empty($u->jabatan)) â€” <span class="muted">{{ $u->jabatan }}</span>@endif
                @if(!empty($u->unit)) , <span class="muted">{{ $u->unit }}</span>@endif
              </li>
            @endforeach
          </ol>
        @else
          <div class="muted">Belum ada penerima undangan yang tercatat.</div>
        @endif

        <div class="divider"></div>

        <p class="mb-0">
          Demikian undangan ini disampaikan. Atas perhatian dan kehadirannya, kami ucapkan terima kasih.
        </p>
      </div>
    @endif
  </div>
</div>
@endsection


