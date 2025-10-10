@extends('layouts.app')
@section('title','Notulensi • Sudah Dibuat')

@section('style')
<style>
  .btn-icon{
    width:34px;height:34px;border-radius:10px;
    display:inline-flex;align-items:center;justify-content:center;
    border:1px solid rgba(255,255,255,.14); color:#fff;
    transition:.15s ease; padding:0;
  }
  .btn-teal   { background: linear-gradient(180deg,#14b8a6,#0d9488); }
  .btn-indigo { background: linear-gradient(180deg,#6366f1,#4f46e5); }
  .btn-purple { background: linear-gradient(180deg,#a855f7,#7e22ce); }
  .btn-icon:hover{ filter:brightness(1.06); }

  .table thead th{ text-align:center; vertical-align:middle; }
  .table.no-hover tbody tr:hover{ background:transparent!important; }

  .step-badge{display:inline-flex;align-items:center;gap:.35rem;border-radius:999px;padding:.2rem .55rem;font-size:.78rem;border:1px solid rgba(255,255,255,.15);margin-right:.35rem;margin-bottom:.35rem;}
  .step-ok{background:rgba(34,197,94,.18)}
  .step-reject{background:rgba(239,68,68,.18)}
  .step-pending{background:rgba(250,204,21,.18)}
  .step-blocked{background:rgba(148,163,184,.18)}
  .muted{opacity:.8}
  .badge{font-weight:700}
  .mini th, .mini td { padding:.45rem .6rem; vertical-align: top; }
  .mini th { background: rgba(148,163,184,.12); }
  .nowrap { white-space: nowrap; }
  .w-notes { max-width: 360px; }
</style>
@endsection

@section('content')
@php
  $daftar = $rapat ?? $rapatSudah ?? $rapat_sudah ?? collect();
  $kategoriList = $daftar_kategori ?? $kategori ?? collect();
  $isPaginator = method_exists($daftar, 'total');
  $startNumber = $isPaginator ? ($daftar->currentPage()-1)*$daftar->perPage() + 1 : 1;

  $popBadgeClass = fn($s) => $s==='approved'?'badge-success':($s==='rejected'?'badge-danger':'badge-warning');
  $stepIcon = fn($s)=>$s==='approved'?'✔':($s==='rejected'?'✖':($s==='blocked'?'🔒':'⏳'));
  $stepClass = fn($s)=>$s==='approved'?'step-badge step-ok':($s==='rejected'?'step-badge step-reject':($s==='blocked'?'step-badge step-blocked':'step-badge step-pending'));
@endphp

<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h3 class="mb-0">Rapat Sudah Memiliki Notulensi</h3>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  {{-- FILTER --}}
  <form method="GET" action="{{ route('notulensi.sudah') }}" class="card mb-3">
    <div class="card-body">
      <div class="form-row align-items-end">
        <div class="col-md-3">
          <label class="mb-1">Kategori Rapat</label>
          <select name="kategori" class="custom-select custom-select-sm">
            <option value="">Semua Kategori</option>
            @foreach($kategoriList as $kat)
              <option value="{{ $kat->id }}" {{ request('kategori')==$kat->id?'selected':'' }}>
                {{ $kat->nama }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="mb-1">Tanggal</label>
          <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-4">
          <label class="mb-1">Cari Judul/Nomor/Tempat</label>
          <input type="text" name="keyword" value="{{ request('keyword') }}" class="form-control form-control-sm" placeholder="Ketik kata kunci ...">
        </div>
        <div class="col-md-2">
          <button class="btn btn-primary btn-block btn-sm">Filter</button>
        </div>
      </div>
    </div>
  </form>

  {{-- DATA --}}
  <div class="card">
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <thead>
          <tr class="text-center">
            <th style="width:54px">#</th>
            <th style="min-width:160px;">Nomor Undangan</th>
            <th style="min-width:260px;">Judul &amp; Kategori</th>
            <th style="min-width:240px;">Tanggal, Waktu &amp; Tempat</th>
            <th style="min-width:130px;">Jumlah Hadir</th>
            <th style="min-width:120px;">Approval</th>
            <th style="width:170px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse(($isPaginator ? $daftar : collect($daftar)) as $i => $r)
            @php
              $jumlahHadir = \DB::table('absensi')
                  ->where('id_rapat', $r->id)
                  ->where('status', 'hadir')
                  ->count();

              $notulensiSteps = \DB::table('approval_requests as ar')
                  ->leftJoin('users as u','u.id','=','ar.approver_user_id')
                  ->select('ar.order_index','ar.status','ar.signed_at','ar.rejected_at','ar.rejection_note','u.name')
                  ->where('ar.rapat_id',$r->id)
                  ->where('ar.doc_type','notulensi')
                  ->orderBy('ar.order_index')
                  ->get();

              $hasSteps=$notulensiSteps->count()>0;
              $hasReject=$notulensiSteps->contains(fn($s)=>$s->status==='rejected');
              $allApproved=$hasSteps && $notulensiSteps->every(fn($s)=>$s->status==='approved');
              $overall=$hasReject?'rejected':($allApproved?'approved':'pending');
              $modalId='apprNotulensi-'.$r->id;
            @endphp

            <tr>
              <td class="text-center">{{ $startNumber + $i }}</td>

              {{-- Nomor undangan rata kiri --}}
              <td>{{ $r->nomor_undangan ?? '—' }}</td>

              {{-- Judul + Kategori --}}
              <td>
                <strong>{{ $r->judul }}</strong>
                <div class="text-muted" style="font-size:12px;">
                  {{ $r->nama_kategori ?? '-' }}
                </div>
              </td>

              {{-- Tanggal & Tempat --}}
              <td>
                {{ \Carbon\Carbon::parse($r->tanggal)->translatedFormat('l, d F Y') }}
                <div class="text-muted" style="font-size:12px;">{{ $r->waktu_mulai }}</div>
                <div class="text-muted" style="font-size:13px;"><i class="fas fa-map-marker-alt mr-1"></i>{{ $r->tempat }}</div>
              </td>

              {{-- Jumlah Hadir --}}
              <td class="text-center">
                {{ $jumlahHadir }} <span class="text-muted" style="font-size:12px">Orang</span>
              </td>

              {{-- Approval Notulensi --}}
              <td class="text-center">
                <button type="button" class="btn btn-sm badge {{ $popBadgeClass($overall) }}" style="font-size:.85rem"
                        data-toggle="modal" data-target="#{{ $modalId }}">
                  Cek Status
                </button>

                {{-- Pop-up modal --}}
                <div class="modal fade" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-hidden="true">
                  <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content modal-solid">
                      <div class="modal-header">
                        <h5 class="modal-title">Status Approval Notulensi</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                      </div>
                      <div class="modal-body">
                        <div class="mb-3">
                          <span class="badge {{ $popBadgeClass($overall) }}">
                            {{ $overall==='approved'?'Semua Disetujui':($overall==='rejected'?'Ada Penolakan':'Menunggu/Pending') }}
                          </span>
                        </div>

                        <div class="mb-2">
                          <div class="text-muted mb-1">Ringkasan</div>
                          @if($notulensiSteps->count())
                            @foreach($notulensiSteps as $st)
                              <span class="{{ $stepClass($st->status) }}" title="{{ $st->name ?? 'Approver' }} • Step {{ $st->order_index }}">
                                <b>{{ $stepIcon($st->status) }}</b> Step {{ $st->order_index }}
                              </span>
                            @endforeach
                          @else
                            <div class="muted">Belum ada konfigurasi approval notulensi.</div>
                          @endif
                        </div>
                        <hr>

                        <h6 class="mb-2">Rincian</h6>
                        @if($notulensiSteps->count())
                          <div class="table-responsive">
                            <table class="table table-sm mini">
                              <thead>
                                <tr>
                                  <th>Step</th>
                                  <th>Approver</th>
                                  <th>Status</th>
                                  <th>Waktu</th>
                                  <th>Catatan Penolakan</th>
                                </tr>
                              </thead>
                              <tbody>
                                @foreach($notulensiSteps as $st)
                                  <tr>
                                    <td>#{{ $st->order_index }}</td>
                                    <td>{{ $st->name ?? 'Approver' }}</td>
                                    <td>
                                      @if($st->status==='approved')
                                        <span class="badge badge-success">Approved</span>
                                      @elseif($st->status==='rejected')
                                        <span class="badge badge-danger">Rejected</span>
                                      @elseif($st->status==='blocked')
                                        <span class="badge badge-secondary">Blocked</span>
                                      @else
                                        <span class="badge badge-warning">Pending</span>
                                      @endif
                                    </td>
                                    <td>
                                      @if($st->status==='approved' && $st->signed_at)
                                        {{ \Carbon\Carbon::parse($st->signed_at)->translatedFormat('d M Y H:i') }}
                                      @elseif($st->status==='rejected' && $st->rejected_at)
                                        {{ \Carbon\Carbon::parse($st->rejected_at)->translatedFormat('d M Y H:i') }}
                                      @else
                                        —
                                      @endif
                                    </td>
                                    <td>
                                      @if($st->status==='rejected' && $st->rejection_note)
                                        {{ $st->rejection_note }}
                                      @else
                                        <span class="muted">—</span>
                                      @endif
                                    </td>
                                  </tr>
                                @endforeach
                              </tbody>
                            </table>
                          </div>
                        @else
                          <div class="muted">Belum ada konfigurasi approval notulensi.</div>
                        @endif
                      </div>
                      @php $hasRejectNot = $notulensiSteps->contains(fn($s)=>$s->status==='rejected'); @endphp
                      <div class="modal-footer">
                        @if($hasRejectNot && !empty($r->id_notulensi))
                          <a href="{{ route('notulensi.edit', $r->id_notulensi) }}" class="btn btn-danger btn-sm">
                            <i class="fas fa-tools mr-1"></i> Perbaiki
                          </a>
                        @endif
                        <button class="btn btn-outline-light btn-sm" data-dismiss="modal">Tutup</button>
                      </div>
                    </div>
                  </div>
                </div>
              </td>

              {{-- Aksi --}}
              <td class="text-center">
                <div class="d-inline-flex align-items-center">
                  <a href="{{ route('notulensi.show', $r->id_notulensi) }}" class="btn-icon btn-teal mr-1" data-toggle="tooltip" title="Lihat Notulen">
                    <i class="fas fa-eye"></i>
                  </a>
                  <a href="{{ route('notulensi.edit', $r->id_notulensi) }}" class="btn-icon btn-indigo mr-1" data-toggle="tooltip" title="Edit Notulen">
                    <i class="fas fa-edit"></i>
                  </a>
                  @if(Route::has('notulensi.cetak.gabung'))
                  <a href="{{ route('notulensi.cetak.gabung', $r->id_notulensi) }}" target="_blank" class="btn-icon btn-purple" data-toggle="tooltip" title="Cetak Gabung (PDF)">
                    <i class="fas fa-file-pdf"></i>
                  </a>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted p-4">Belum ada notulen yang dibuat.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  @if($isPaginator)
    <div class="mt-3">
      {{ $daftar->appends(request()->query())->links() }}
    </div>
  @endif
</div>
@endsection
