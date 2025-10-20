@extends('layouts.app')

@section('content')
<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Daftar Rapat</h3>
        @if(in_array(Auth::user()->role, ['admin','operator']))
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalTambahRapat">
                + Tambah Rapat
            </button>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- ===================== FILTER ===================== --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('rapat.index') }}">
                <div class="form-row align-items-end">
                    <div class="col-md-3 mb-2">
                        <label class="mb-1 text-muted">Kategori Rapat</label>
                        <select name="kategori" class="form-control form-control-sm">
                            <option value="">Semua Kategori</option>
                            @foreach($daftar_kategori as $kategori)
                                <option value="{{ $kategori->id }}" {{ request('kategori') == $kategori->id ? 'selected' : '' }}>
                                    {{ $kategori->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="mb-1 text-muted">Tanggal</label>
                        <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="mb-1 text-muted">Cari Judul/Nomor/Tempat</label>
                        <input type="text" name="keyword" value="{{ request('keyword') }}" class="form-control form-control-sm" placeholder="Ketik kata kunci ...">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="mb-1 d-none d-md-block">&nbsp;</label>
                        <button class="btn btn-primary btn-block btn-sm">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ====== STYLE & HELPER ====== --}}
    <style>
        .aksi-wrap{gap:8px;}
        .aksi-btn{
            width:36px;height:36px;border-radius:12px;
            display:inline-flex;align-items:center;justify-content:center;
            border:1px solid rgba(255,255,255,.15);
        }
        .aksi-view{background:#0ea5e9;}
        .aksi-edit{background:#f59e0b;}
        .aksi-del{background:#ef4444;}
        .aksi-btn i{color:#fff;}
        .aksi-btn:hover{filter:brightness(1.05);}
        .table thead th{text-align:center;}
        td.td-aksi{white-space:nowrap;}
        .badge{font-weight:700}

        .step-badge{display:inline-flex;align-items:center;gap:.35rem;border-radius:999px;padding:.2rem .55rem;font-size:.78rem;border:1px solid rgba(255,255,255,.15);margin-right:.35rem;margin-bottom:.35rem;}
        .step-ok{background:rgba(34,197,94,.18)}
        .step-reject{background:rgba(239,68,68,.18)}
        .step-pending{background:rgba(250,204,21,.18)}
        .step-blocked{background:rgba(148,163,184,.18)}
        .muted{opacity:.8}

        .mini th, .mini td { padding:.45rem .6rem; vertical-align: top; }
        .mini th { background: rgba(148,163,184,.12); }
        .nowrap { white-space: nowrap; }
        .w-notes { max-width: 340px; }

        /* ===== Mobile Cards ===== */
        .m-wrap{padding:0}
        .m-card{
            background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.02));
            border:1px solid rgba(226,232,240,.15);
            border-radius:12px;
            color:var(--text);
            padding:12px;
            margin-bottom:10px;
        }
        .m-title{font-weight:800; color:#fff; line-height:1.25}
        .m-sub{color:#9fb0cd; font-size:.86rem}
        .m-meta{display:flex; flex-wrap:wrap; gap:8px; margin-top:8px}
        .mchip{
            display:inline-flex; align-items:center; gap:.3rem;
            padding:.18rem .5rem; border-radius:999px; font-size:.72rem; font-weight:800;
            background:rgba(79,70,229,.22); border:1px solid rgba(79,70,229,.35); color:#fff;
        }
        .mchip.ok{ background:rgba(34,197,94,.22); border-color:rgba(34,197,94,.35) }
        .mchip.warn{ background:rgba(245,158,11,.2); border-color:rgba(245,158,11,.35) }
        .mchip.err{ background:rgba(239,68,68,.18); border-color:rgba(239,68,68,.35) }
        .m-actions{display:flex; flex-wrap:wrap; gap:8px; margin-top:10px}
        .btn-pill{
            display:inline-flex; align-items:center; gap:6px;
            border:1px solid rgba(255,255,255,.18);
            padding:.42rem .7rem; border-radius:10px; font-size:.88rem; color:#fff; text-decoration:none;
            background:rgba(255,255,255,.06);
        }
        .btn-pill.primary{ background:linear-gradient(180deg,var(--primary),var(--primary-700)); border-color:transparent; }
        .btn-pill.warn{ background:#f59e0b; border-color:#f59e0b }
        .btn-pill.danger{ background:#ef4444; border-color:#ef4444 }
    </style>

    @php
        // Warna badge pop tombol "Cek Status"
        $popBadgeClass = function (string $overall) {
            return $overall === 'approved' ? 'badge-success'
                 : ($overall === 'rejected' ? 'badge-danger' : 'badge-warning');
        };
        // Icon ringkas tiap step
        $stepIcon = function ($s) {
            return $s === 'approved' ? '✔'
                 : ($s === 'rejected' ? '✖'
                 : ($s === 'blocked'  ? '🔒' : '⏳'));
        };
        // Class ringkas tiap step
        $stepClass = function ($s) {
            return $s === 'approved' ? 'step-badge step-ok'
                 : ($s === 'rejected' ? 'step-badge step-reject'
                 : ($s === 'blocked'  ? 'step-badge step-blocked' : 'step-badge step-pending'));
        };
        // Overall status dari 2 jenis dokumen
        $overallStatus = function (array $map) {
            $types = ['undangan','absensi'];
            $hasReject = false; $allApproved = true; $hasAny = false;

            foreach ($types as $t) {
                if (!empty($map[$t])) {
                    $hasAny = true;
                    foreach ($map[$t] as $row) {
                        if ($row['status'] === 'rejected') $hasReject = true;
                        if ($row['status'] !== 'approved') $allApproved = false;
                    }
                } else {
                    $allApproved = false;
                }
            }
            if ($hasReject) return 'rejected';
            if ($hasAny && $allApproved) return 'approved';
            return 'pending';
        };
        // Label status utama untuk ditampilkan
        $overallLabel = function (string $overall) {
            return $overall === 'approved' ? 'Semua Disetujui'
                 : ($overall === 'rejected' ? 'Ada Penolakan'
                 : 'Menunggu / Proses');
        };
    @endphp

    {{-- ===== Desktop Table ===== --}}
    <div class="card d-none d-md-block">
        <div class="card-body p-0">
            <table class="table table-striped table-sm m-0">
                <thead>
                    <tr>
                        <th style="width:60px">#</th>
                        <th>Nomor Undangan</th>
                        <th>Judul &amp; Kategori</th>
                        <th>Waktu &amp; Tempat</th>
                        <th>Dibuat Oleh</th>
                        <th>Status Rapat</th>
                        <th style="width:140px">Approval</th>
                        <th style="width:120px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($daftar_rapat as $no => $rapat)
                    @php
                        // Bangun peta approval (UNDANGAN & ABSENSI) dan deduplikasi jika ada duplikat
                        $approvalMap = $rapat->approval_map ?? null;
                        if (!$approvalMap) {
                            $steps = DB::table('approval_requests as ar')
                                ->leftJoin('users as u','u.id','=','ar.approver_user_id')
                                ->select(
                                    'ar.doc_type','ar.order_index','ar.status',
                                    'ar.signed_at','ar.rejected_at','ar.rejection_note',
                                    'u.name'
                                )
                                ->where('ar.rapat_id', $rapat->id)
                                ->whereIn('ar.doc_type', ['undangan','absensi'])
                                ->orderBy('ar.doc_type')
                                ->orderBy('ar.order_index')
                                ->get();

                            $approvalMap = $steps->groupBy('doc_type')->map(function($g){
                                return $g->unique(function($r){
                                    return ($r->order_index ?? 0).'|'.($r->name ?? '');
                                })->values()->map(function($r){
                                    return [
                                        'order'          => (int)$r->order_index,
                                        'name'           => $r->name ?: 'Approver',
                                        'status'         => $r->status,
                                        'signed_at'      => $r->signed_at,
                                        'rejected_at'    => $r->rejected_at,
                                        'rejection_note' => $r->rejection_note,
                                    ];
                                })->all();
                            })->toArray();
                        }

                        $overall = $overallStatus($approvalMap);
                        $modalId = 'apprModal-'.$rapat->id;
                    @endphp

                    <tr>
                        <td class="text-center">{{ $daftar_rapat->firstItem() + $no }}</td>
                        <td>{{ $rapat->nomor_undangan }}</td>

                        {{-- Judul + Kategori --}}
                        <td>
                            <div class="font-weight-bold">{{ $rapat->judul }}</div>
                            <div class="text-muted" style="font-size:.85rem">
                                {{ $rapat->nama_kategori ?? '-' }}
                            </div>
                        </td>

                        {{-- Waktu + Tempat --}}
                        <td>
                            <div>
                                {{ \Carbon\Carbon::parse($rapat->tanggal)->translatedFormat('l, d F Y') }}
                                <span class="text-muted">{{ $rapat->waktu_mulai }}</span>
                            </div>
                            <div class="text-muted" style="font-size:.85rem">
                                {{ $rapat->tempat }}
                            </div>
                        </td>

                        <td>{{ $rapat->nama_pembuat ?? '-' }}</td>

                        <td>
                            <span class="badge
                                @if($rapat->status_label == 'Akan Datang') badge-info
                                @elseif($rapat->status_label == 'Berlangsung') badge-success
                                @elseif($rapat->status_label == 'Selesai') badge-secondary
                                @elseif($rapat->status_label == 'Dibatalkan') badge-danger
                                @endif">
                                {{ $rapat->status_label }}
                            </span>
                        </td>

                        {{-- ====== Approval: tombol + keterangan utama ====== --}}
                        <td class="text-center">
                            <button type="button"
                                    class="btn btn-sm badge {{ $popBadgeClass($overall) }}"
                                    style="font-size:.85rem"
                                    data-toggle="modal"
                                    data-target="#{{ $modalId }}">
                                Cek Status
                            </button>

                            <div class="mt-1" style="font-size:.8rem;
                                 color: {{ $overall==='approved' ? '#22c55e' : ($overall==='rejected' ? '#ef4444' : '#f59e0b') }};">
                              {{ $overallLabel($overall) }}
                            </div>

                            {{-- Modal detail (dipakai juga oleh mobile) --}}
                            @include('rapat.partials._approval_modal', [
                                'modalId' => $modalId,
                                'overall' => $overall,
                                'approvalMap' => $approvalMap,
                                'stepIcon' => $stepIcon,
                                'stepClass' => $stepClass,
                                'popBadgeClass' => $popBadgeClass,
                                'rapat' => $rapat
                            ])
                        </td>

                        <td class="text-center td-aksi">
                            <div class="d-inline-flex aksi-wrap">
                                <a href="{{ route('rapat.show', $rapat->id) }}"
                                   class="aksi-btn aksi-view" title="Detail">
                                    <i class="fa fa-eye"></i>
                                </a>

                                @if(in_array(Auth::user()->role, ['admin','operator']))
                                    <button type="button"
                                            class="aksi-btn aksi-edit"
                                            data-toggle="modal"
                                            data-target="#modalEditRapat-{{ $rapat->id }}"
                                            title="Edit">
                                        <i class="fa fa-edit"></i>
                                    </button>

                                    <form action="{{ route('rapat.destroy', $rapat->id) }}"
                                          method="POST"
                                          onsubmit="return confirm('Hapus rapat ini?')"
                                          class="d-inline">
                                        @csrf @method('DELETE')
                                        <button class="aksi-btn aksi-del" title="Hapus" type="submit">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center">Belum ada data rapat.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== Mobile Cards ===== --}}
    <div class="d-block d-md-none">
        @forelse($daftar_rapat as $no => $rapat)
            @php
                $approvalMap = $rapat->approval_map ?? null;
                if (!$approvalMap) {
                    $steps = DB::table('approval_requests as ar')
                        ->leftJoin('users as u','u.id','=','ar.approver_user_id')
                        ->select(
                            'ar.doc_type','ar.order_index','ar.status',
                            'ar.signed_at','ar.rejected_at','ar.rejection_note',
                            'u.name'
                        )
                        ->where('ar.rapat_id', $rapat->id)
                        ->whereIn('ar.doc_type', ['undangan','absensi'])
                        ->orderBy('ar.doc_type')->orderBy('ar.order_index')->get();

                    $approvalMap = $steps->groupBy('doc_type')->map(function($g){
                        return $g->unique(function($r){
                            return ($r->order_index ?? 0).'|'.($r->name ?? '');
                        })->values()->map(function($r){
                            return [
                                'order'          => (int)$r->order_index,
                                'name'           => $r->name ?: 'Approver',
                                'status'         => $r->status,
                                'signed_at'      => $r->signed_at,
                                'rejected_at'    => $r->rejected_at,
                                'rejection_note' => $r->rejection_note,
                            ];
                        })->all();
                    })->toArray();
                }

                $overall = $overallStatus($approvalMap);
                $modalId = 'apprModal-'.$rapat->id;

                $overallChipClass = $overall==='approved' ? 'ok' : ($overall==='rejected' ? 'err' : 'warn');
            @endphp

            <div class="m-card">
                <div class="m-title">{{ $rapat->judul }}</div>
                @if(!empty($rapat->nama_kategori))
                    <div class="m-sub">{{ $rapat->nama_kategori }}</div>
                @endif>

                <div class="m-meta">
                    <span class="mchip">
                        No: {{ $rapat->nomor_undangan ?? '—' }}
                    </span>
                    <span class="mchip">
                        {{ \Carbon\Carbon::parse($rapat->tanggal)->translatedFormat('d M Y') }} • {{ $rapat->waktu_mulai }}
                    </span>
                    <span class="mchip">{{ $rapat->tempat }}</span>
                    <span class="mchip {{ $overallChipClass }}">{{ $overallLabel($overall) }}</span>
                    <span class="mchip" style="
                        background:
                        @if($rapat->status_label=='Akan Datang') rgba(14,165,233,.22)
                        @elseif($rapat->status_label=='Berlangsung') rgba(34,197,94,.22)
                        @elseif($rapat->status_label=='Selesai') rgba(148,163,184,.22)
                        @else rgba(239,68,68,.22) @endif;
                        border-color: rgba(255,255,255,.25);">
                        Status: {{ $rapat->status_label }}
                    </span>
                </div>

                <div class="m-actions">
                    <a href="{{ route('rapat.show', $rapat->id) }}" class="btn-pill">
                        <i class="fas fa-eye"></i> Detail
                    </a>

                    <button type="button"
                            class="btn-pill primary"
                            data-toggle="modal"
                            data-target="#{{ $modalId }}">
                        <i class="fas fa-clipboard-check"></i> Cek Status
                    </button>

                    @if(in_array(Auth::user()->role, ['admin','operator']))
                        <button type="button"
                                class="btn-pill warn"
                                data-toggle="modal"
                                data-target="#modalEditRapat-{{ $rapat->id }}">
                            <i class="fas fa-edit"></i> Edit
                        </button>

                        <form action="{{ route('rapat.destroy', $rapat->id) }}"
                              method="POST"
                              onsubmit="return confirm('Hapus rapat ini?')"
                              class="d-inline">
                            @csrf @method('DELETE')
                            <button class="btn-pill danger" type="submit">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                        </form>
                    @endif
                </div>

                {{-- Modal detail approval untuk mobile (reuse partial) --}}
                @include('rapat.partials._approval_modal', [
                    'modalId' => $modalId,
                    'overall' => $overall,
                    'approvalMap' => $approvalMap,
                    'stepIcon' => $stepIcon,
                    'stepClass' => $stepClass,
                    'popBadgeClass' => $popBadgeClass,
                    'rapat' => $rapat
                ])
            </div>
        @empty
            <div class="text-center text-muted">Belum ada data rapat.</div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-3 d-flex justify-content-right">
        {{ $daftar_rapat->onEachSide(1)->links() }}
    </div>
</div>

{{-- ===================== Modal Tambah ===================== --}}
<div class="modal fade" id="modalTambahRapat" tabindex="-1" role="dialog" aria-labelledby="tambahRapatLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content modal-solid">
      <div class="modal-header">
        <h5 class="modal-title" id="tambahRapatLabel">Tambah Rapat Baru</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form action="{{ route('rapat.store') }}" method="POST" autocomplete="off">
        @csrf
        <div class="modal-body">
          @if ($errors->any() && session('from_modal') == 'tambah_rapat')
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
          @endif
          @include('rapat._form', [
            'rapat' => null,
            'peserta_terpilih' => [],
            'daftar_kategori' => $daftar_kategori,
            'approval1_list' => $approval1_list,
            'approval2_list' => $approval2_list,
            'daftar_peserta' => $daftar_peserta,
            'dropdownParentId' => '#modalTambahRapat',
            'pesertaWrapperId' => 'peserta-wrapper-tambah'
          ])
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- ===================== Modal Edit per Rapat ===================== --}}
@foreach($daftar_rapat as $rapat)
<div class="modal fade" id="modalEditRapat-{{ $rapat->id }}" tabindex="-1" role="dialog" aria-labelledby="editRapatLabel-{{ $rapat->id }}" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content modal-solid">
      <div class="modal-header">
        <h5 class="modal-title" id="editRapatLabel-{{ $rapat->id }}">Edit Rapat: {{ $rapat->judul }}</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form action="{{ route('rapat.update', $rapat->id) }}" method="POST" autocomplete="off">
        @csrf
        @method('PUT')
        <div class="modal-body">
          @include('rapat._form', [
            'rapat' => $rapat,
            'peserta_terpilih' => $rapat->peserta_terpilih ?? [],
            'daftar_kategori' => $daftar_kategori,
            'approval1_list' => $approval1_list,
            'approval2_list' => $approval2_list,
            'daftar_peserta' => $daftar_peserta,
            'dropdownParentId' => '#modalEditRapat-' . $rapat->id,
            'pesertaWrapperId' => 'peserta-wrapper-edit-' . $rapat->id
          ])
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endforeach

@push('scripts')
<script>
  // Tutup modal approval, lalu buka modal edit yang sesuai
  function openEditFromApproval(approvalModalId, editModalId){
    var $appr = $('#'+approvalModalId);
    $appr.on('hidden.bs.modal', function(){
      $('#'+editModalId).modal('show');
      $appr.off('hidden.bs.modal');
    });
    $appr.modal('hide');
  }
</script>
@endpush

@endsection
