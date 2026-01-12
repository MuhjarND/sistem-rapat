@extends('layouts.app')

@section('title','Absensi Rapat')

@section('style')
<style>
  /* ====== Umum ====== */
  .table.no-hover tbody tr:hover { background: transparent !important; }
  .table thead th{ text-align:center; vertical-align:middle; font-size:13px; white-space:nowrap; }
  .table td{ font-size:13px; vertical-align:middle; }

  /* Badge kecil */
  .pill {
    display:inline-flex; align-items:center; justify-content:center;
    min-width: 28px; height: 22px; padding: 0 6px;
    border-radius: 999px;
    font-weight: 600; font-size: 12px; color:#fff;
  }
  .pill-hadir { background: linear-gradient(180deg, #3b82f6, #2563eb); }
  .pill-total { background: linear-gradient(180deg, #22c55e, #16a34a); margin-left:4px; }

  /* Progress mini */
  .progress-mini{
    width:100%;
    height:6px;
    background: rgba(255,255,255,.12);
    border-radius: 999px;
    overflow:hidden;
    margin-top:6px;
  }
  .progress-mini > span{
    display:block; height:100%;
    background: linear-gradient(90deg,#22c55e,#16a34a);
  }
  .pct-text{ font-size:11px; color:#bfc9dd; margin-top:2px; }

  /* Tombol ikon */
  .btn-icon{
    width:30px; height:30px; border-radius:8px;
    display:inline-flex; align-items:center; justify-content:center;
    border:none; color:#fff; font-size:13px; margin:0 2px;
  }
  .btn-teal   { background: linear-gradient(180deg,#14b8a6,#0d9488); }
  .btn-indigo { background: linear-gradient(180deg,#6366f1,#4f46e5); }
  .btn-wa     { background: linear-gradient(180deg,#ef4444,#dc2626); } /* merah: belum absen */
  .btn-wa-all { background: linear-gradient(180deg,#10b981,#059669); } /* hijau: semua */
  .btn-teal:hover, .btn-indigo:hover, .btn-wa:hover, .btn-wa-all:hover { filter: brightness(1.08); }

  /* ====== Mobile cards ====== */
  .abs-card{
    border:1px solid var(--border);
    border-radius:14px;
    background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.02));
    box-shadow:var(--shadow); color:var(--text);
    margin-bottom:12px;
  }
  .abs-card .card-body{ padding:14px 16px; }
  .abs-head{ display:flex; align-items:center; gap:.6rem; margin-bottom:6px; }
  .abs-title{ font-weight:800; line-height:1.25; }
  .abs-kat{ font-size:.8rem; color:var(--muted); }
  .abs-meta{ font-size:.86rem; color:#c7d2fe; display:flex; flex-wrap:wrap; gap:.35rem .6rem; }
  .abs-meta .dot{ opacity:.5 }
  .abs-progress{ margin-top:8px; }
  .abs-actions{ display:flex; align-items:center; justify-content:space-between; gap:.5rem; margin-top:10px; }
  .abs-actions .left, .abs-actions .right{ display:flex; align-items:center; gap:.35rem; flex-wrap:wrap; }
  .abs-num{ font-weight:700; }
  .abs-loc{ color:var(--muted); font-size:.86rem; }
</style>
@endsection

@section('content')
<div class="container">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Daftar Absensi Rapat</h3>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  {{-- FILTER --}}
  <form method="GET" action="{{ route('absensi.index') }}" class="card mb-3">
    <div class="card-body py-3">
      <div class="form-row align-items-end">
        <div class="col-md-3">
          <label class="mb-1 small">Kategori Rapat</label>
          <select name="kategori" class="custom-select custom-select-sm">
            <option value="">Semua Kategori</option>
            @foreach($daftar_kategori as $kat)
              <option value="{{ $kat->id }}" {{ request('kategori')==$kat->id ? 'selected':'' }}>
                {{ $kat->nama }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="mb-1 small">Tanggal</label>
          <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-4">
          <label class="mb-1 small">Cari Judul/Nomor/Tempat</label>
          <input type="text" name="keyword" value="{{ request('keyword') }}"
                 class="form-control form-control-sm"
                 placeholder="Ketik kata kunci ...">
        </div>
        <div class="col-md-2">
          <button class="btn btn-primary btn-block btn-sm">Filter</button>
        </div>
      </div>
    </div>
  </form>

  {{-- ===================== DESKTOP (TABLE) ===================== --}}
  <div class="card d-none d-md-block">
    <div class="card-body p-0">
      <table class="table table-sm mb-0 no-hover">
        <thead>
          <tr class="text-center">
            <th style="width:40px">#</th>
            <th style="min-width:160px;">Nomor Undangan</th>
            <th>Judul &amp; Kategori</th>
            <th style="min-width:230px;">Tanggal, Waktu &amp; Tempat</th>
            <th style="width:140px;">Kehadiran</th>
            <th style="width:140px;">Kirim WA</th>
            <th style="width:110px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($daftar_rapat as $index => $rapat)
            @php
              $jumlahHadir = \DB::table('absensi')->where('id_rapat', $rapat->id)->where('status','hadir')->count();
              $jumlahUndangan = (int) ($rapat->jumlah_peserta ?? 0);
              $pct = $jumlahUndangan > 0 ? round($jumlahHadir * 100 / $jumlahUndangan) : 0;
            @endphp
            <tr>
              <td class="text-center">
                {{ ($daftar_rapat->currentPage()-1) * $daftar_rapat->perPage() + $index + 1 }}
              </td>

              <td>{{ $rapat->nomor_undangan ?? 'â€”' }}</td>

              <td>
                <strong>{{ $rapat->judul }}</strong>
                <div class="text-muted" style="font-size:12px">{{ $rapat->nama_kategori ?? '-' }}</div>
              </td>

              <td>
                {{ \Carbon\Carbon::parse($rapat->tanggal)->translatedFormat('l, d F Y') }}
                <div class="text-muted" style="font-size:11px">{{ \App\Helpers\TimeHelper::short($rapat->waktu_mulai) }}</div>
                <div class="text-muted" style="font-size:12px">
                  <i class="fas fa-map-marker-alt mr-1"></i>{{ $rapat->tempat }}
                </div>
              </td>

              {{-- Kehadiran + Progress --}}
              <td>
                <div class="text-center">
                  <span>{{ $jumlahHadir }}</span>
                  <span style="margin:0 4px;">/</span>
                  <span>{{ $jumlahUndangan }}</span>
                </div>
                <div class="progress-mini" title="{{ $pct }}%">
                  <span style="width: {{ $pct }}%;"></span>
                </div>
                <div class="pct-text text-center">{{ $pct }}%</div>
              </td>

              {{-- Kirim WA --}}
              <td class="text-center">
                {{-- WA BELUM ABSEN (merah) --}}
                <form action="{{ route('absensi.notify.start', $rapat->id) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Kirim link absensi via WhatsApp ke peserta yang BELUM absen?')">
                  @csrf
                  <button class="btn-icon btn-wa" data-toggle="tooltip" title="Kirim WA (belum absen)">
                    <i class="fab fa-whatsapp"></i>
                  </button>
                </form>

                {{-- WA SEMUA (hijau) --}}
                <form action="{{ route('absensi.notify.start', $rapat->id) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Kirim link absensi via WhatsApp ke SEMUA peserta?')">
                  @csrf
                  <input type="hidden" name="all" value="1">
                  <button class="btn-icon btn-wa-all" data-toggle="tooltip" title="Kirim WA (semua peserta)">
                    <i class="fab fa-whatsapp"></i><span class="sr-only"> all</span>
                  </button>
                </form>
              </td>

              {{-- Aksi --}}
              <td class="text-center">
                <a href="{{ route('rapat.show', $rapat->id) }}"
                   class="btn-icon btn-teal"
                   data-toggle="tooltip" title="Detail Rapat">
                  <i class="fas fa-eye"></i>
                </a>
                <a href="{{ route('absensi.export.pdf', $rapat->id) }}"
                   target="_blank"
                   class="btn-icon btn-indigo"
                   data-toggle="tooltip" title="Unduh PDF Absensi">
                  <i class="fas fa-file-download"></i>
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center text-muted p-4">
                Belum ada rapat untuk absensi.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- ===================== MOBILE (CARD LIST) ===================== --}}
  <div class="d-md-none">
    @forelse($daftar_rapat as $index => $rapat)
      @php
        $jumlahHadir = \DB::table('absensi')->where('id_rapat', $rapat->id)->where('status','hadir')->count();
        $jumlahUndangan = (int) ($rapat->jumlah_peserta ?? 0);
        $pct = $jumlahUndangan > 0 ? round($jumlahHadir * 100 / $jumlahUndangan) : 0;
      @endphp

      <div class="abs-card">
        <div class="card-body">
          {{-- Header judul + nomor --}}
          <div class="abs-head">
            <div class="abs-title">{{ $rapat->judul }}</div>
          </div>
          <div class="abs-kat">
            No: {{ $rapat->nomor_undangan ?? 'â€”' }} â€¢ {{ $rapat->nama_kategori ?? '-' }}
          </div>

          {{-- Meta waktu & lokasi --}}
          <div class="abs-meta mt-1">
            <span>{{ \Carbon\Carbon::parse($rapat->tanggal)->translatedFormat('d M Y') }}</span>
            <span class="dot">â€¢</span>
            <span>{{ \App\Helpers\TimeHelper::short($rapat->waktu_mulai) }}</span>
          </div>
          <div class="abs-loc mt-1">
            <i class="fas fa-map-marker-alt mr-1"></i>{{ $rapat->tempat }}
          </div>

          {{-- Kehadiran --}}
          <div class="abs-progress">
            <div><span class="abs-num">{{ $jumlahHadir }}</span> / {{ $jumlahUndangan }} hadir</div>
            <div class="progress-mini" title="{{ $pct }}%">
              <span style="width: {{ $pct }}%;"></span>
            </div>
            <div class="pct-text">{{ $pct }}%</div>
          </div>

          {{-- Actions --}}
          <div class="abs-actions">
            <div class="left">
              {{-- WA belum absen --}}
              <form action="{{ route('absensi.notify.start', $rapat->id) }}" method="POST"
                    onsubmit="return confirm('Kirim link absensi via WhatsApp ke peserta yang BELUM absen?')">
                @csrf
                <button class="btn-icon btn-wa" title="Kirim WA (belum absen)">
                  <i class="fab fa-whatsapp"></i>
                </button>
              </form>
              {{-- WA semua --}}
              <form action="{{ route('absensi.notify.start', $rapat->id) }}" method="POST"
                    onsubmit="return confirm('Kirim link absensi via WhatsApp ke SEMUA peserta?')">
                @csrf
                <input type="hidden" name="all" value="1">
                <button class="btn-icon btn-wa-all" title="Kirim WA (semua)">
                  <i class="fab fa-whatsapp"></i>
                </button>
              </form>
            </div>
            <div class="right">
              <a href="{{ route('rapat.show', $rapat->id) }}" class="btn btn-sm btn-outline-light">
                Detail
              </a>
              <a href="{{ route('absensi.export.pdf', $rapat->id) }}" target="_blank" class="btn btn-sm btn-primary">
                <i class="fas fa-file-download mr-1"></i> PDF
              </a>
            </div>
          </div>
        </div>
      </div>
    @empty
      <div class="text-center text-muted p-3">Belum ada rapat untuk absensi.</div>
    @endforelse
  </div>

  {{-- PAGINATION --}}
  <div class="mt-3">
    {{ $daftar_rapat->appends(request()->query())->links() }}
  </div>

</div>
@endsection


