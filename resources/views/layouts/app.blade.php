<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Sistem Manajemen Rapat')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Google Font --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Bootstrap 4 + FontAwesome --}}
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    {{-- Select2 (v4.0.13 stabil) --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        :root{
            --bg:#0f172a;--panel:#0b1026;--muted:#9fb0cd;--text:#e6eefc;
            --primary:#4f46e5;--primary-700:#4338ca;--accent:#22c55e;
            --danger:#ef4444;--warning:#f59e0b;--info:#0ea5e9;--border:#1f2a4d;
            --shadow:0 10px 30px rgba(2,6,23,.35);--radius:14px;
            --nav-h: 56px; --sidebar-w: 260px;
        }

        html,body{height:100%}
        body{
            background:var(--bg);color:var(--text);font-family:"Inter",system-ui,-apple-system,Segoe UI,Roboto,"Helvetica Neue",Arial,"Noto Sans";
            -webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale;position:relative;min-height:100vh;
            padding-top: var(--nav-h);
        }
        body::before{
            content:"";position:fixed;inset:0;z-index:-1;
            background:
              radial-gradient(1200px 600px at -10% -10%, rgba(99,102,241,.25), transparent 55%),
              radial-gradient(1000px 500px at 110% 10%, rgba(14,165,233,.25), transparent 55%),
              linear-gradient(180deg, var(--bg) 0%, #0b1026 100%);
            background-repeat:no-repeat;pointer-events:none;
        }

        .navbar{
            position: fixed; top: 0; left: 0; right: 0; z-index: 1030;
            height: var(--nav-h);
            background:linear-gradient(180deg, rgba(17,24,39,.75) 0%, rgba(17,24,39,.35) 100%);
            backdrop-filter:saturate(180%) blur(10px);
            border-bottom:1px solid rgba(99,102,241,.2)
        }
        .navbar-brand{color:#fff!important;font-weight:800;letter-spacing:.2px}
        .navbar .btn-danger{border-radius:999px;padding:.35rem .75rem}
        .btn-hamburger{border-color:rgba(255,255,255,.25);color:#fff}
        .btn-hamburger i{font-size:1rem}

        .container-fluid{padding-left:0;padding-right:0}

        .sidebar{
            position:fixed; top: var(--nav-h); left:0;
            width: var(--sidebar-w); height: calc(100vh - var(--nav-h));
            overflow-y:auto;
            background:
                linear-gradient(180deg, rgba(99,102,241,.12), rgba(14,165,233,.08)),
                linear-gradient(180deg, rgba(17,24,39,.85), rgba(17,24,39,.60));
            border-right:1px solid rgba(99,102,241,.18);
            box-shadow:var(--shadow);
            padding:24px 16px;
            z-index: 1040;
            transform: none;
            transition: transform .2s ease;
        }
        .sidebar::-webkit-scrollbar{width:6px}
        .sidebar::-webkit-scrollbar-thumb{background:rgba(255,255,255,.15);border-radius:10px}
        .sidebar::-webkit-scrollbar-thumb:hover{background:rgba(255,255,255,.25)}

        .sidebar .nav-link{
            color:var(--muted);font-weight:600;border-radius:12px;padding:.55rem .7rem;transition:all .18s ease;
            display:flex;align-items:center;gap:10px;
        }
        .sidebar .nav-link i{width:22px;text-align:center;margin-right:8px}
        .sidebar .nav-link:hover{color:#fff;background:rgba(79,70,229,.18)}
        .sidebar .nav-link.active{
            color:#fff;background:linear-gradient(90deg, rgba(79,70,229,.35), rgba(14,165,233,.25));
            box-shadow:inset 0 0 0 1px rgba(79,70,229,.25)
        }
        .submenu{margin-left:6px;padding-left:10px;border-left:1px dashed rgba(99,102,241,.25)}

        .badge-chip{
          margin-left:auto; display:inline-flex;align-items:center;gap:.35rem;
          background:#ef4444;color:#fff; border-radius:999px;
          padding:.22rem .55rem; font-size:.72rem;font-weight:800;line-height:1;
          letter-spacing:.2px;white-space:nowrap; border:1px solid rgba(255,255,255,.25);
          box-shadow:var(--shadow);
        }
        .badge-chip.info{background:#0ea5e9;}
        .badge-chip.success{background:#22c55e;}
        .badge-chip.warn{background:#f59e0b;}

        .sidebar-backdrop{ position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1035; display:none; }
        .sidebar-backdrop.show{display:block}

        .content-area{ margin-left: var(--sidebar-w); width: calc(100vw - var(--sidebar-w)); max-width: 100%; padding: 32px 28px 36px 28px; }

        .row > [class*="col-"] > .card{height:100%}
        .scroll-y{max-height: 340px; overflow-y:auto}
        .scroll-y::-webkit-scrollbar{width:6px}
        .scroll-y::-webkit-scrollbar-thumb{background:rgba(255,255,255,.15);border-radius:10px}

        @media (max-width: 991.98px){
            .sidebar{ padding:16px; transform: translateX(-100%); height: calc(100vh - var(--nav-h)); }
            .sidebar.is-open{ transform: translateX(0); }
            .content-area{ margin-left: 0; width: 100%; padding: 24px 16px; }
        }

        .card{background:linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.02));border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);color:var(--text)}
        .card .card-header{background:transparent;border-bottom:1px solid var(--border);font-weight:700;color:#fff}

        .table{color:var(--text)}
        .table thead th{
            text-transform:uppercase;letter-spacing:.4px;font-size:.75rem;text-align:center;vertical-align:middle;
            background:rgba(79,70,229,.12);border-top:none;border-bottom:1px solid var(--border)
        }
        .table td{vertical-align:middle;font-size:.86rem}
        .table-hover tbody tr:hover{background:rgba(148,163,184,.08)!important;color:var(--text)!important}

        .table .badge{border-radius:999px;padding:.35rem .6rem;font-weight:700;letter-spacing:.3px}

        .btn{border-radius:10px;font-weight:600}
        .btn-primary{background:linear-gradient(180deg, var(--primary), var(--primary-700));border-color:transparent}
        .btn-primary:hover{filter:brightness(1.05)}
        .btn-outline-light{border-color:rgba(255,255,255,.25);color:#fff}
        .btn-icon{width:34px;height:34px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;padding:0;border:1px solid rgba(255,255,255,.18);background:rgba(255,255,255,.06)}
        .btn-icon:hover{background:rgba(255,255,255,.14)}

        .form-control,.custom-select{background:rgba(255,255,255,.06);border:1px solid rgba(226,232,240,.15);color:var(--text)}
        .form-control:focus,.custom-select:focus{background:rgba(255,255,255,.08);border-color:rgba(79,70,229,.55);box-shadow:0 0 0 .2rem rgba(79,70,229,.25);color:var(--text)}
        label{font-weight:600;color:#dbe7ff;font-size:.85rem}

        .select2-container{width:100%!important}
        .select2-container--default .select2-selection--multiple{background:rgba(255,255,255,.06);border:1px solid rgba(226,232,240,.15);min-height:38px}
        .select2-container--default .select2-selection--multiple .select2-selection__choice{background:rgba(79,70,229,.25);border:1px solid rgba(79,70,229,.35);color:#fff;border-radius:999px;padding:2px 8px}
        .select2-dropdown{background:#0f1533;color:#fff;border:1px solid var(--border)}
        .select2-results__option--highlighted{background:rgba(79,70,229,.45)!important}
        .select2-container .select2-dropdown{z-index:2000!important}

        .modal-content{background:linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,.02));border:1px solid var(--border);border-radius:calc(var(--radius) - 6px);color:var(--text)}
        .modal-header{border-bottom:1px solid var(--border)}
        .modal-footer{border-top:1px solid var(--border)}
        .modal-content.modal-solid{background:#0f1533!important;border:1px solid var(--border);border-radius:calc(var(--radius) - 6px);color:var(--text)}
        .modal-solid .modal-header,.modal-solid .modal-footer{background:#0f1533!important;border-color:var(--border)}
        .modal-solid .form-control,.modal-solid .custom-select,.modal-solid textarea{background:#0d1330!important;border:1px solid rgba(226,232,240,.2);color:var(--text)}
        .modal-solid .form-control:focus,.modal-solid .custom-select:focus,.modal-solid textarea:focus{background:#101740!important;border-color:rgba(79,70,229,.55);box-shadow:0 0 0 .2rem rgba(79,70,229,.25);color:var(--text)}
        .modal-solid .select2-container--default .select2-selection--multiple{background:#0d1330!important;border:1px solid rgba(226,232,240,.2);min-height:38px}
        .modal-solid .select2-dropdown{background:#0d1330!important;color:#fff;border:1px solid var(--border)}
        .modal-backdrop.show{opacity:.6}

        .text-muted{color:var(--muted)!important}
        .data-card{display:flex;flex-direction:column;height:calc(100vh - 240px)}
        .data-card .data-scroll{overflow:auto;flex:1 1 auto}
        .data-card .data-scroll thead th{position:sticky;top:0;z-index:2;background:rgba(79,70,229,.12)}
        .pagination{margin-bottom:0}
        .page-item .page-link{background:rgba(255,255,255,.06);border:1px solid rgba(226,232,240,.15);color:var(--text)}
        .page-item.active .page-link{background:linear-gradient(180deg, var(--primary), var(--primary-700));border-color:transparent;color:#fff}
        .page-item.disabled .page-link{color:var(--muted)}
        .form-control[readonly],.form-control:disabled{background:rgba(255,255,255,.06)!important;border:1px solid rgba(226,232,240,.15)!important;color:var(--text)!important;opacity:1}

        .ck-editor__editable{background:rgba(255,255,255,.06)!important;color:var(--text)!important;border:1px solid rgba(226,232,240,.2)!important;border-radius:8px!important;padding:10px!important;min-height:140px}
        .ck.ck-toolbar{background:#1e293b!important;border:1px solid rgba(226,232,240,.2)!important;border-radius:8px 8px 0 0!important}
        .ck.ck-toolbar .ck-button .ck-icon,.ck.ck-toolbar .ck-button .ck-label{color:var(--text)!important}
        .ck.ck-toolbar .ck-button:hover{background:rgba(99,70,229,.2)!important}
    </style>

    @yield('style')
    @stack('style')
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
        <div class="d-flex align-items-center">
            <button class="btn btn-outline-light btn-sm d-inline-flex d-md-none btn-hamburger mr-2" id="btnSidebarToggle" type="button" aria-label="Buka menu">
                <i class="fas fa-bars"></i>
            </button>

            <a class="navbar-brand d-flex align-items-center mb-0" href="{{ url('/') }}">
                {{-- Logo --}}
                <span class="mr-2 d-inline-flex align-items-center justify-content-center"
                    style="width:38px;height:38px;border-radius:8px;overflow:hidden;box-shadow:var(--shadow)">
                    <img src="{{ asset('logo_qr.png') }}" alt="Logo PTA Papua Barat" style="width:100%;height:100%;object-fit:contain;">
                </span>

                {{-- Nama aplikasi --}}
                <span style="font-weight:700; letter-spacing:.3px;">SISTEM RAPAT</span>
            </a>
        </div>

        <div class="ml-auto d-flex align-items-center">
            <span class="mr-3 text-muted"><i class="far fa-user mr-1"></i>{{ Auth::user()->name ?? '' }}</span>
            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                @csrf
                <button class="btn btn-outline-light btn-sm">Logout</button>
            </form>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row no-gutters">
            <!-- Sidebar -->
            <div id="appSidebar" class="sidebar">
                @php
                  $role = Auth::user()->role ?? null;
                  $isPeserta  = $role === 'peserta';
                  $isNotulis  = in_array($role, ['notulis','notulensi']);
                  $isApproval = $role === 'approval';
                  $isOperator = $role === 'operator';
                @endphp

                @if($isPeserta)
                    {{-- =================== SIDEBAR KHUSUS PESERTA =================== --}}
                    @php
                        $userId = Auth::id();

                        $absensiPendingCount = \DB::table('undangan')
                            ->join('rapat','undangan.id_rapat','=','rapat.id')
                            ->leftJoin('absensi', function($q) use ($userId){
                                $q->on('absensi.id_rapat','=','rapat.id')->where('absensi.id_user','=',$userId);
                            })
                            ->where('undangan.id_user',$userId)
                            ->whereDate('rapat.tanggal','<=', now()->toDateString())
                            ->whereNull('absensi.id')
                            ->count();

                        $upcomingCount = \DB::table('undangan')
                            ->join('rapat','undangan.id_rapat','=','rapat.id')
                            ->where('undangan.id_user',$userId)
                            ->whereDate('rapat.tanggal','>=', now()->toDateString())
                            ->count();

                        $tugasPendingCount = \DB::table('notulensi_tugas')
                            ->where('user_id', $userId)
                            ->where('status', 'pending')
                            ->count();
                    @endphp

                    <nav class="nav flex-column">
                        <a class="nav-link {{ request()->routeIs('peserta.dashboard') ? 'active' : '' }}"
                        href="{{ route('peserta.dashboard') }}">
                            <i class="fas fa-chart-pie"></i> Dashboard
                            @if($absensiPendingCount>0)
                            <span class="badge-chip warn" title="Absensi pending">{{ $absensiPendingCount }}</span>
                            @endif
                        </a>

                        <a class="nav-link d-flex align-items-center {{ request()->routeIs('peserta.rapat') ? 'active' : '' }}"
                        href="{{ route('peserta.rapat') }}">
                            <i class="fas fa-calendar-alt"></i> Rapat
                            @if($upcomingCount>0)
                            <span class="badge-chip info" title="Rapat akan datang">{{ $upcomingCount }}</span>
                            @endif
                        </a>

                        <a class="nav-link d-flex align-items-center {{ request()->routeIs('peserta.tugas.*') ? 'active' : '' }}"
                        href="{{ route('peserta.tugas.index') }}">
                            <i class="fas fa-tasks"></i> Tugas Saya
                            @if($tugasPendingCount>0)
                            <span class="badge-chip warn" title="Tugas perlu diselesaikan">{{ $tugasPendingCount }}</span>
                            @endif
                        </a>
                    </nav>

                @elseif($isNotulis)
                    {{-- =================== SIDEBAR KHUSUS NOTULENSI =================== --}}
                    @php
                        $countBelum = \DB::table('rapat')
                                        ->leftJoin('notulensi','notulensi.id_rapat','=','rapat.id')
                                        ->whereNull('notulensi.id')->count();
                        $countSudah = \DB::table('rapat')
                                        ->join('notulensi','notulensi.id_rapat','=','rapat.id')
                                        ->count();
                    @endphp

                    <nav class="nav flex-column">
                        <a class="nav-link {{ request()->routeIs('notulensi.dashboard') ? 'active' : '' }}"
                           href="{{ route('notulensi.dashboard') }}">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>

                        <a class="nav-link d-flex align-items-center"
                           data-toggle="collapse" href="#menuNotu" role="button"
                           aria-expanded="true" aria-controls="menuNotu">
                            <i class="fas fa-book-open"></i> Notulensi
                            <i class="ml-auto fas fa-angle-down"></i>
                        </a>
                        <div class="collapse show" id="menuNotu">
                            <div class="nav flex-column submenu">
                                <a class="nav-link d-flex justify-content-between align-items-center {{ request()->routeIs('notulensi.belum') ? 'active' : '' }}"
                                   href="{{ route('notulensi.belum') }}">
                                    <span class="d-inline-flex align-items-center">
                                      <i class="fas fa-times-circle mr-2"></i> Belum Ada
                                    </span>
                                    @if($countBelum>0) <span class="badge-chip">{{ $countBelum }}</span> @endif
                                </a>
                                <a class="nav-link d-flex justify-content-between align-items-center {{ request()->routeIs('notulensi.sudah') ? 'active' : '' }}"
                                   href="{{ route('notulensi.sudah') }}">
                                    <span class="d-inline-flex align-items-center">
                                      <i class="fas fa-check-circle mr-2"></i> Sudah Ada
                                    </span>
                                    @if($countSudah>0) <span class="badge-chip success">{{ $countSudah }}</span> @endif
                                </a>
                            </div>
                        </div>
                    </nav>

                @elseif($isApproval)
                    {{-- =================== SIDEBAR KHUSUS APPROVAL =================== --}}
                    @php
                        $uid = Auth::id();

                        $pendingAll = \DB::table('approval_requests as ar')
                            ->where('ar.approver_user_id',$uid)
                            ->where('ar.status','pending');

                        $pendingOpen = (clone $pendingAll)
                            ->whereNotExists(function($q){
                                $q->select(\DB::raw(1))
                                ->from('approval_requests as prev')
                                ->whereColumn('prev.rapat_id','ar.rapat_id')
                                ->whereColumn('prev.doc_type','ar.doc_type')
                                ->whereColumn('prev.order_index','<','ar.order_index')
                                ->where('prev.status','!=','approved');
                            })->count();

                        $byType = \DB::table('approval_requests as ar')
                            ->select('doc_type', \DB::raw('COUNT(*) as total'))
                            ->where('approver_user_id',$uid)
                            ->where('status','pending')
                            ->groupBy('doc_type')
                            ->pluck('total','doc_type');
                        $pUndangan  = (int) ($byType['undangan']  ?? 0);
                        $pNotulensi = (int) ($byType['notulensi'] ?? 0);
                        $pAbsensi   = (int) ($byType['absensi']   ?? 0);
                    @endphp

                    <nav class="nav flex-column">
                        <a class="nav-link {{ request()->routeIs('approval.dashboard') ? 'active' : '' }}"
                        href="{{ route('approval.dashboard') }}">
                            <i class="fas fa-user-check"></i> Dashboard Approval
                        </a>

                        <a class="nav-link d-flex align-items-center {{ request()->routeIs('approval.pending') ? 'active' : '' }}"
                        href="{{ route('approval.pending') }}">
                            <i class="fas fa-inbox"></i> Antrian Tanda Tangan
                            @if($pendingOpen>0)
                            <span class="badge-chip info" title="Siap ditandatangani">{{ $pendingOpen }}</span>
                            @endif
                        </a>

                        <a class="nav-link d-flex align-items-center {{ request()->routeIs('approval.approved') ? 'active' : '' }}"
                        href="{{ route('approval.approved') }}">
                            <i class="fas fa-file-signature"></i> Dokumen Disetujui
                        </a>
                        
                        <a class="nav-link d-flex align-items-center {{ request()->routeIs('approval.rapat') ? 'active' : '' }}"
                        href="{{ route('approval.rapat') }}">
                            <i class="fas fa-calendar-alt"></i> Rapat
                        </a>
                    </nav>

                @elseif($isOperator)
                    {{-- =================== SIDEBAR KHUSUS OPERATOR =================== --}}
                    @php
                        // Notulensi counter (sama seperti admin)
                        $countBelum = \DB::table('rapat')
                                        ->leftJoin('notulensi','notulensi.id_rapat','=','rapat.id')
                                        ->whereNull('notulensi.id')->count();
                        $countSudah = \DB::table('rapat')
                                        ->join('notulensi','notulensi.id_rapat','=','rapat.id')
                                        ->count();

                        // Laporan badge (sama seperti admin)
                        $countRapatAktif = \DB::table('rapat')
                            ->leftJoin('laporan_archived_meetings as lam','lam.rapat_id','=','rapat.id')
                            ->whereNull('lam.id')->count();
                        $countUploadsAktif = \DB::table('laporan_files')->where('is_archived',0)->count();
                        $badgeLaporan = $countRapatAktif + $countUploadsAktif;
                        $badgeArsip   = \DB::table('laporan_files')->where('is_archived',1)->count();

                        // Tugas saya (reuse halaman peserta)
                        $myId = Auth::id();
                        $tugasPendingCount = \DB::table('notulensi_tugas')
                            ->where('user_id', $myId)
                            ->where('status', 'pending')
                            ->count();
                    @endphp

                    <nav class="nav flex-column">
                        <a class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}" href="{{ url('/dashboard') }}">
                            <i class="fas fa-home"></i> Dashboard
                        </a>

                        <a class="nav-link {{ request()->is('rapat*') ? 'active' : '' }}" href="{{ route('rapat.index') }}">
                            <i class="fas fa-calendar-alt"></i> Rapat
                        </a>

                        <a class="nav-link {{ request()->is('absensi*') ? 'active' : '' }}" href="{{ route('absensi.index') }}">
                            <i class="fas fa-clipboard-list"></i> Absensi
                        </a>

                        {{-- Laporan --}}
                        <a class="nav-link d-flex align-items-center" data-toggle="collapse" href="#menuLaporanOp" role="button" aria-expanded="true" aria-controls="menuLaporanOp">
                            <i class="fas fa-folder-open"></i> Laporan
                            <i class="ml-auto fas fa-angle-down"></i>
                        </a>
                        <div class="collapse show" id="menuLaporanOp">
                            <div class="nav flex-column submenu">
                                <a class="nav-link d-flex justify-content-between align-items-center {{ request()->routeIs('laporan.index') ? 'active' : '' }}"
                                   href="{{ route('laporan.index') }}">
                                    <span class="d-inline-flex align-items-center">
                                      <i class="fas fa-file-invoice mr-2"></i> Laporan
                                    </span>
                                    @if($badgeLaporan>0) <span class="badge-chip info">{{ $badgeLaporan }}</span> @endif
                                </a>
                                <a class="nav-link d-flex justify-content-between align-items-center {{ request()->routeIs('laporan.arsip') ? 'active' : '' }}"
                                   href="{{ route('laporan.arsip') }}">
                                    <span class="d-inline-flex align-items-center">
                                      <i class="fas fa-archive mr-2"></i> Arsip Laporan
                                    </span>
                                    @if($badgeArsip>0) <span class="badge-chip">{{ $badgeArsip }}</span> @endif
                                </a>
                            </div>
                        </div>

                        {{-- Tugas Saya (reuse route peserta.tugas.index) --}}
                        <a class="nav-link d-flex align-items-center {{ request()->routeIs('peserta.tugas.*') ? 'active' : '' }}"
                           href="{{ route('peserta.tugas.index') }}">
                            <i class="fas fa-tasks"></i> Tugas Saya
                            @if($tugasPendingCount>0)
                            <span class="badge-chip warn" title="Tugas perlu diselesaikan">{{ $tugasPendingCount }}</span>
                            @endif
                        </a>
                    </nav>

                @else
                    {{-- =================== SIDEBAR DEFAULT (ADMIN/DLL) =================== --}}
                    @php
                        $countBelum = \DB::table('rapat')
                                        ->leftJoin('notulensi','notulensi.id_rapat','=','rapat.id')
                                        ->whereNull('notulensi.id')->count();
                        $countSudah = \DB::table('rapat')
                                        ->join('notulensi','notulensi.id_rapat','=','rapat.id')
                                        ->count();

                        $countRapatAktif = \DB::table('rapat')
                            ->leftJoin('laporan_archived_meetings as lam','lam.rapat_id','=','rapat.id')
                            ->whereNull('lam.id')->count();

                        $countUploadsAktif = \DB::table('laporan_files')->where('is_archived',0)->count();

                        $badgeLaporan = $countRapatAktif + $countUploadsAktif;
                        $badgeArsip   = \DB::table('laporan_files')->where('is_archived',1)->count();
                    @endphp

                    <nav class="nav flex-column">
                        <a class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}" href="{{ url('/dashboard') }}">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                        <a class="nav-link {{ request()->is('rapat*') ? 'active' : '' }}" href="{{ route('rapat.index') }}">
                            <i class="fas fa-calendar-alt"></i> Rapat
                        </a>
                        <a class="nav-link {{ request()->is('absensi*') ? 'active' : '' }}" href="{{ route('absensi.index') }}">
                            <i class="fas fa-clipboard-list"></i> Absensi
                        </a>

                        <a class="nav-link d-flex align-items-center" data-toggle="collapse" href="#menuNotulensi" role="button" aria-expanded="true" aria-controls="menuNotulensi">
                            <i class="fas fa-book-open"></i> Notulensi
                            <i class="ml-auto fas fa-angle-down"></i>
                        </a>
                        <div class="collapse show" id="menuNotulensi">
                            <div class="nav flex-column submenu">
                                <a class="nav-link d-flex justify-content-between align-items-center {{ request()->routeIs('notulensi.belum') ? 'active' : '' }}"
                                   href="{{ route('notulensi.belum') }}">
                                    <span class="d-inline-flex align-items-center">
                                      <i class="fas fa-times-circle mr-2"></i> Belum Ada
                                    </span>
                                    @if($countBelum>0) <span class="badge-chip">{{ $countBelum }}</span> @endif
                                </a>
                                <a class="nav-link d-flex justify-content-between align-items-center {{ request()->routeIs('notulensi.sudah') ? 'active' : '' }}"
                                   href="{{ route('notulensi.sudah') }}">
                                    <span class="d-inline-flex align-items-center">
                                      <i class="fas fa-check-circle mr-2"></i> Sudah Ada
                                    </span>
                                    @if($countSudah>0) <span class="badge-chip success">{{ $countSudah }}</span> @endif
                                </a>
                            </div>
                        </div>

                        <a class="nav-link d-flex align-items-center" data-toggle="collapse" href="#menuLaporan" role="button" aria-expanded="true" aria-controls="menuLaporan">
                            <i class="fas fa-folder-open"></i> Laporan
                            <i class="ml-auto fas fa-angle-down"></i>
                        </a>
                        <div class="collapse show" id="menuLaporan">
                            <div class="nav flex-column submenu">
                                <a class="nav-link d-flex justify-content-between align-items-center {{ request()->routeIs('laporan.index') ? 'active' : '' }}"
                                   href="{{ route('laporan.index') }}">
                                    <span class="d-inline-flex align-items-center">
                                      <i class="fas fa-file-invoice mr-2"></i> Laporan
                                    </span>
                                    @if($badgeLaporan>0) <span class="badge-chip info">{{ $badgeLaporan }}</span> @endif
                                </a>
                                <a class="nav-link d-flex justify-content-between align-items-center {{ request()->routeIs('laporan.arsip') ? 'active' : '' }}"
                                   href="{{ route('laporan.arsip') }}">
                                    <span class="d-inline-flex align-items-center">
                                      <i class="fas fa-archive mr-2"></i> Arsip Laporan
                                    </span>
                                    @if($badgeArsip>0) <span class="badge-chip">{{ $badgeArsip }}</span> @endif
                                </a>
                            </div>
                        </div>

                        <a class="nav-link d-flex align-items-center" data-toggle="collapse" href="#menuKelolaData" role="button" aria-expanded="true" aria-controls="menuKelolaData">
                            <i class="fas fa-database"></i> Kelola Data
                            <i class="ml-auto fas fa-angle-down"></i>
                        </a>
                        <div class="collapse show" id="menuKelolaData">
                            <div class="nav flex-column submenu">
                                <a class="nav-link {{ request()->is('user*') ? 'active' : '' }}" href="{{ route('user.index') }}">
                                    <i class="fas fa-users"></i> User
                                </a>
                                <a class="nav-link {{ request()->is('units*') ? 'active' : '' }}" href="{{ route('units.index') }}">
                                    <i class="fas fa-layer-group"></i> Unit
                                </a>
                                <a class="nav-link {{ request()->is('kategori*') ? 'active' : '' }}" href="{{ route('kategori.index') }}">
                                    <i class="fas fa-layer-group"></i> Kategori Rapat
                                </a>
                            </div>
                        </div>
                    </nav>
                @endif
            </div>

            <div id="sidebarBackdrop" class="sidebar-backdrop"></div>

            <main class="content-area container-fluid">
                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

    <script>
    (function(){
        var btn = document.getElementById('btnSidebarToggle');
        var sidebar = document.getElementById('appSidebar');
        var backdrop = document.getElementById('sidebarBackdrop');

        function openSidebar(){ sidebar.classList.add('is-open'); backdrop.classList.add('show'); document.body.style.overflow='hidden'; }
        function closeSidebar(){ sidebar.classList.remove('is-open'); backdrop.classList.remove('show'); document.body.style.overflow=''; }

        if(btn){ btn.addEventListener('click', function(e){ e.preventDefault(); sidebar.classList.contains('is-open') ? closeSidebar() : openSidebar(); }); }
        if(backdrop){ backdrop.addEventListener('click', closeSidebar); }

        Array.prototype.forEach.call(document.querySelectorAll('#appSidebar a.nav-link'), function(a){
            a.addEventListener('click', function(){ if (window.innerWidth < 992) closeSidebar(); });
        });
    })();
    </script>

    <script>
    $(function(){
      // Paksa semua collapse terbuka
      $('.collapse').each(function(){
        var $col = $(this);
        $col.addClass('show');
        var targetId = $col.attr('id');
        if (targetId){
          var $toggle = $('[data-toggle="collapse'][href="#'+targetId+'"], [data-toggle="collapse"][data-target="#'+targetId+'"]');
          $toggle.removeClass('collapsed').attr('aria-expanded','true');
        }
      });
      // Tooltip + Select2
      $('[data-toggle="tooltip"]').tooltip();
    });
    </script>

    <script>
      // Select2 init helper (modal)
      $(function() {
        function initSelect2In($container){
            var $selects = $container.find('.js-example-basic-multiple');
            $selects.each(function(){
                var $sel = $(this);
                if ($sel.data('select2')) $sel.select2('destroy');
                var parentSelector = $sel.attr('data-dropdown-parent');
                var $parent = parentSelector ? $(parentSelector) : $container;
                if (!$parent.length) $parent = $('body');
                $sel.select2({ width:'100%', dropdownParent:$parent, placeholder:'Pilih peserta rapat', allowClear:true });
            });
        }
        $('#modalTambahRapat')
          .on('shown.bs.modal', function(){ initSelect2In($(this)); })
          .on('hidden.bs.modal', function(){ var $sel=$(this).find('.js-example-basic-multiple'); if ($sel.data('select2')) $sel.select2('destroy'); });

        $('[id^="modalEditRapat-"]')
          .on('shown.bs.modal', function(){ initSelect2In($(this)); })
          .on('hidden.bs.modal', function(){ var $sel=$(this).find('.js-example-basic-multiple'); if ($sel.data('select2')) $sel.select2('destroy'); });
      });
    </script>

    <style>
      .select2-container .select2-dropdown { z-index: 2000 !important; }
      .select2-container { width: 100% !important; }
    </style>

    @yield('script')
    @stack('scripts')
</body>
</html>
