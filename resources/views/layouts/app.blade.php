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
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    {{-- Select2 (v4.0.13 stabil) --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        :root{
            --bg:#0f172a;--panel:#0b1026;--muted:#9fb0cd;--text:#e6eefc;
            --primary:#4f46e5;--primary-700:#4338ca;--accent:#22c55e;
            --danger:#ef4444;--warning:#f59e0b;--info:#0ea5e9;--border:#1f2a4d;
            --shadow:0 10px 30px rgba(2,6,23,.35);--radius:14px;
        }
        html,body{height:100%}
        body{
            background:var(--bg);color:var(--text);font-family:"Inter",system-ui,-apple-system,Segoe UI,Roboto,"Helvetica Neue",Arial,"Noto Sans";
            -webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale;position:relative;min-height:100vh;
        }
        body::before{
            content:"";position:fixed;inset:0;z-index:-1;
            background:
              radial-gradient(1200px 600px at -10% -10%, rgba(99,102,241,.25), transparent 55%),
              radial-gradient(1000px 500px at 110% 10%, rgba(14,165,233,.25), transparent 55%),
              linear-gradient(180deg, var(--bg) 0%, #0b1026 100%);
            background-repeat:no-repeat;pointer-events:none;
        }

        /* NAVBAR */
        .navbar{background:linear-gradient(180deg, rgba(17,24,39,.75) 0%, rgba(17,24,39,.35) 100%);backdrop-filter:saturate(180%) blur(10px);border-bottom:1px solid rgba(99,102,241,.2)}
        .navbar-brand{color:#fff!important;font-weight:800;letter-spacing:.2px}
        .navbar .btn-danger{border-radius:999px;padding:.35rem .75rem}

        /* LAYOUT */
        .container-fluid{padding-left:0;padding-right:0}
        .content-area{padding:28px}

        /* SIDEBAR */
        .sidebar{
            min-height:calc(100vh - 56px);
            background:
                linear-gradient(180deg, rgba(99,102,241,.12), rgba(14,165,233,.08)),
                linear-gradient(180deg, rgba(17,24,39,.85), rgba(17,24,39,.60));
            border-right:1px solid rgba(99,102,241,.18);box-shadow:var(--shadow);padding:24px 16px
        }
        .sidebar .nav-link{color:var(--muted);font-weight:600;border-radius:12px;padding:.55rem .7rem;transition:all .18s ease}
        .sidebar .nav-link i{width:22px;text-align:center;margin-right:8px}
        .sidebar .nav-link:hover{color:#fff;background:rgba(79,70,229,.18)}
        .sidebar .nav-link.active{color:#fff;background:linear-gradient(90deg, rgba(79,70,229,.35), rgba(14,165,233,.25));box-shadow:inset 0 0 0 1px rgba(79,70,229,.25)}
        .submenu{margin-left:6px;padding-left:10px;border-left:1px dashed rgba(99,102,241,.25)}
        .badge-ping{background:var(--danger);color:#fff;border-radius:999px;padding:.25rem .5rem;font-size:.75rem;box-shadow:var(--shadow)}

        /* CARD */
        .card{background:linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.02));border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);color:var(--text)}
        .card .card-header{background:transparent;border-bottom:1px solid var(--border);font-weight:700;color:#fff}

        /* TABLE */
        .table{color:var(--text)}
        .table thead th{
            text-transform:uppercase;letter-spacing:.4px;font-size:.75rem;text-align:center;vertical-align:middle;
            background:rgba(79,70,229,.12);border-top:none;border-bottom:1px solid var(--border)
        }
        .table td{vertical-align:middle;font-size:.86rem}
        .table-hover tbody tr:hover{background:rgba(14,165,233,.06)}
        .table .badge{border-radius:999px;padding:.35rem .6rem;font-weight:700;letter-spacing:.3px}

        /* BUTTONS */
        .btn{border-radius:10px;font-weight:600}
        .btn-primary{background:linear-gradient(180deg, var(--primary), var(--primary-700));border-color:transparent}
        .btn-primary:hover{filter:brightness(1.05)}
        .btn-outline-light{border-color:rgba(255,255,255,.25);color:#fff}
        .btn-icon{width:34px;height:34px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;padding:0;border:1px solid rgba(255,255,255,.18);background:rgba(255,255,255,.06)}
        .btn-icon:hover{background:rgba(255,255,255,.14)}

        /* FORMS */
        .form-control,.custom-select{background:rgba(255,255,255,.06);border:1px solid rgba(226,232,240,.15);color:var(--text)}
        .form-control:focus,.custom-select:focus{background:rgba(255,255,255,.08);border-color:rgba(79,70,229,.55);box-shadow:0 0 0 .2rem rgba(79,70,229,.25);color:var(--text)}
        label{font-weight:600;color:#dbe7ff;font-size:.85rem}

        /* SELECT2 */
        .select2-container{width:100%!important}
        .select2-container--default .select2-selection--multiple{background:rgba(255,255,255,.06);border:1px solid rgba(226,232,240,.15);min-height:38px}
        .select2-container--default .select2-selection--multiple .select2-selection__choice{background:rgba(79,70,229,.25);border:1px solid rgba(79,70,229,.35);color:#fff;border-radius:999px;padding:2px 8px}
        .select2-dropdown{background:#0f1533;color:#fff;border:1px solid var(--border)}
        .select2-results__option--highlighted{background:rgba(79,70,229,.45)!important}
        .select2-container .select2-dropdown{z-index:2000!important}

        /* MODAL */
        .modal-content{background:linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,.02));border:1px solid var(--border);border-radius:calc(var(--radius) - 6px);color:var(--text)}
        .modal-header{border-bottom:1px solid var(--border)}
        .modal-footer{border-top:1px solid var(--border)}

        /* Modal SOLID */
        .modal-content.modal-solid{background:#0f1533!important;border:1px solid var(--border);border-radius:calc(var(--radius) - 6px);color:var(--text)}
        .modal-solid .modal-header,.modal-solid .modal-footer{background:#0f1533!important;border-color:var(--border)}
        .modal-solid .form-control,.modal-solid .custom-select,.modal-solid textarea{background:#0d1330!important;border:1px solid rgba(226,232,240,.2);color:var(--text)}
        .modal-solid .form-control:focus,.modal-solid .custom-select:focus,.modal-solid textarea:focus{background:#101740!important;border-color:rgba(79,70,229,.55);box-shadow:0 0 0 .2rem rgba(79,70,229,.25);color:var(--text)}
        .modal-solid .select2-container--default .select2-selection--multiple{background:#0d1330!important;border:1px solid rgba(226,232,240,.2);min-height:38px}
        .modal-solid .select2-dropdown{background:#0d1330!important;color:#fff;border:1px solid var(--border)}
        .modal-backdrop.show{opacity:.6}

        /* UTILITIES */
        .text-muted{color:var(--muted)!important}
        .content-area{padding:32px 28px}
        .data-card{display:flex;flex-direction:column;height:calc(100vh - 240px)}
        .data-card .data-scroll{overflow:auto;flex:1 1 auto}
        .data-card .data-scroll thead th{position:sticky;top:0;z-index:2;background:rgba(79,70,229,.12)}
        .pagination{margin-bottom:0}
        .page-item .page-link{background:rgba(255,255,255,.06);border:1px solid rgba(226,232,240,.15);color:var(--text)}
        .page-item.active .page-link{background:linear-gradient(180deg, var(--primary), var(--primary-700));border-color:transparent;color:#fff}
        .page-item.disabled .page-link{color:var(--muted)}
        .form-control[readonly],.form-control:disabled{background:rgba(255,255,255,.06)!important;border:1px solid rgba(226,232,240,.15)!important;color:var(--text)!important;opacity:1}

        /* CKEditor theming (fallback) */
        .ck-editor__editable{background:rgba(255,255,255,.06)!important;color:var(--text)!important;border:1px solid rgba(226,232,240,.2)!important;border-radius:8px!important;padding:10px!important;min-height:140px}
        .ck-editor__editable:focus{border-color:rgba(99,102,241,.6)!important;box-shadow:0 0 0 2px rgba(99,102,241,.3)!important}
        .ck.ck-toolbar{background:#1e293b!important;border:1px solid rgba(226,232,240,.2)!important;border-radius:8px 8px 0 0!important}
        .ck.ck-toolbar .ck-button .ck-icon,.ck.ck-toolbar .ck-button .ck-label{color:var(--text)!important}
        .ck.ck-toolbar .ck-button:hover{background:rgba(99,102,241,.2)!important}
    </style>

    @yield('style')
    @stack('style')
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
        <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
            <span class="mr-2 d-inline-flex align-items-center justify-content-center"
                  style="width:34px;height:34px;border-radius:8px;background:linear-gradient(180deg, rgba(79,70,229,.9), rgba(14,165,233,.9));box-shadow:var(--shadow);">
                <i class="fas fa-cogs"></i>
            </span>
            Manajemen Rapat
        </a>
        <div class="ml-auto d-flex align-items-center">
            <span class="mr-3 text-muted"><i class="far fa-user mr-1"></i>{{ Auth::user()->name ?? '' }}</span>
            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                @csrf
                <button class="btn btn-outline-light btn-sm">Logout</button>
            </form>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar d-none d-md-block">
                @php
                    // buka-tutup "Kelola Data"
                    $openKelola  = request()->is('user*') || request()->is('pimpinan*') || request()->is('kategori*');

                    // ===== Notulensi dropdown + badge =====
                    $openNotulensi = request()->routeIs('notulensi.*') || request()->is('notulensi*');
                    $countBelum = \DB::table('rapat')
                                    ->leftJoin('notulensi','notulensi.id_rapat','=','rapat.id')
                                    ->whereNull('notulensi.id')->count();
                    $countSudah = \DB::table('rapat')
                                    ->join('notulensi','notulensi.id_rapat','=','rapat.id')
                                    ->count();

                    // ===== Laporan dropdown + badge (baru) =====
                    $openLaporan = request()->routeIs('laporan.index') || request()->routeIs('laporan.arsip') || request()->is('laporan/*');

                    // total REKAP RAPAT AKTIF = rapat yang BELUM ada pada tabel penanda archive
                    $countRapatAktif = \DB::table('rapat')
                        ->leftJoin('laporan_archived_meetings as lam','lam.rapat_id','=','rapat.id')
                        ->whereNull('lam.id')
                        ->count();

                    // total UNGGAHAN AKTIF
                    $countUploadsAktif = \DB::table('laporan_files')
                        ->where('is_archived',0)
                        ->count();

                    // badge "Laporan" = rekap aktif + upload aktif
                    $badgeLaporan = $countRapatAktif + $countUploadsAktif;

                    // badge "Arsip Laporan" = semua file terarsip
                    $badgeArsip = \DB::table('laporan_files')
                        ->where('is_archived',1)
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

                    {{-- ================== Notulensi (DROPDOWN) ================== --}}
                    <a class="nav-link d-flex align-items-center {{ $openNotulensi ? '' : 'collapsed' }}"
                       data-toggle="collapse" href="#menuNotulensi" role="button"
                       aria-expanded="{{ $openNotulensi ? 'true' : 'false' }}" aria-controls="menuNotulensi">
                        <i class="fas fa-book-open"></i> Notulensi
                        <i class="ml-auto fas fa-angle-down"></i>
                    </a>
                    <div class="collapse {{ $openNotulensi ? 'show' : '' }}" id="menuNotulensi">
                        <div class="nav flex-column submenu">
                            <a class="nav-link d-flex justify-content-between align-items-center {{ request()->routeIs('notulensi.belum') ? 'active' : '' }}"
                               href="{{ route('notulensi.belum') }}">
                                <span><i class="fas fa-circle mr-2" style="font-size:8px;"></i> Belum Ada</span>
                                @if($countBelum>0)
                                    <span class="badge-ping">{{ $countBelum }}</span>
                                @endif
                            </a>
                            <a class="nav-link d-flex justify-content-between align-items-center {{ request()->routeIs('notulensi.sudah') ? 'active' : '' }}"
                               href="{{ route('notulensi.sudah') }}">
                                <span><i class="fas fa-circle mr-2" style="font-size:8px;"></i> Sudah Ada</span>
                                @if($countSudah>0)
                                    <span class="badge-ping">{{ $countSudah }}</span>
                                @endif
                            </a>
                        </div>
                    </div>
                    {{-- ========================================================== --}}

                    {{-- ================== Laporan (DROPDOWN) ================== --}}
                    <a class="nav-link d-flex align-items-center {{ $openLaporan ? '' : 'collapsed' }}"
                       data-toggle="collapse" href="#menuLaporan" role="button"
                       aria-expanded="{{ $openLaporan ? 'true' : 'false' }}" aria-controls="menuLaporan">
                        <i class="fas fa-folder-open"></i> Laporan
                        <i class="ml-auto fas fa-angle-down"></i>
                    </a>
                    <div class="collapse {{ $openLaporan ? 'show' : '' }}" id="menuLaporan">
                        <div class="nav flex-column submenu">
                            {{-- Halaman Laporan (utama) --}}
                            <a class="nav-link d-flex justify-content-between align-items-center {{ request()->routeIs('laporan.index') ? 'active' : '' }}"
                               href="{{ route('laporan.index') }}">
                                <span><i class="fas fa-circle mr-2" style="font-size:8px;"></i> Laporan</span>
                                @if($badgeLaporan>0)
                                  <span class="badge-ping">{{ $badgeLaporan }}</span>
                                @endif
                            </a>
                            {{-- Arsip Laporan --}}
                            <a class="nav-link d-flex justify-content-between align-items-center {{ request()->routeIs('laporan.arsip') ? 'active' : '' }}"
                               href="{{ route('laporan.arsip') }}">
                                <span><i class="fas fa-circle mr-2" style="font-size:8px;"></i> Arsip Laporan</span>
                                @if($badgeArsip>0)
                                  <span class="badge-ping">{{ $badgeArsip }}</span>
                                @endif
                            </a>
                        </div>
                    </div>
                    {{-- ======================================================== --}}

                    {{-- Kelola Data (dropdown) --}}
                    <a class="nav-link d-flex align-items-center {{ $openKelola ? '' : 'collapsed' }}"
                       data-toggle="collapse" href="#menuKelolaData" role="button"
                       aria-expanded="{{ $openKelola ? 'true' : 'false' }}" aria-controls="menuKelolaData">
                        <i class="fas fa-database"></i> Kelola Data
                        <i class="ml-auto fas fa-angle-down"></i>
                    </a>
                    <div class="collapse {{ $openKelola ? 'show' : '' }}" id="menuKelolaData">
                        <div class="nav flex-column submenu">
                            <a class="nav-link {{ request()->is('user*') ? 'active' : '' }}" href="{{ route('user.index') }}">
                                <i class="fas fa-users"></i> User
                            </a>
                            <a class="nav-link {{ request()->is('pimpinan*') ? 'active' : '' }}" href="{{ route('pimpinan.index') }}">
                                <i class="fas fa-user-tie"></i> Pimpinan
                            </a>
                            <a class="nav-link {{ request()->is('kategori*') ? 'active' : '' }}" href="{{ route('kategori.index') }}">
                                <i class="fas fa-layer-group"></i> Kategori Rapat
                            </a>
                        </div>
                    </div>
                </nav>
            </div>

            <!-- Content Area -->
            <div class="col-md-10 content-area">
                @yield('content')
            </div>
        </div>
    </div>

    <!-- JQUERY FULL -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Select2 JS -->
    <script defer src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

    <script>
    // Init Select2: hanya saat modal tampil (stabil di Bootstrap 4)
    $(function() {
        function initSelect2In($container){
            var $selects = $container.find('.js-example-basic-multiple');
            $selects.each(function(){
                var $sel = $(this);
                if ($sel.data('select2')) $sel.select2('destroy');

                var parentSelector = $sel.attr('data-dropdown-parent');
                var $parent = parentSelector ? $(parentSelector) : $container;
                if (!$parent.length) $parent = $('body');

                $sel.select2({
                    width: '100%',
                    dropdownParent: $parent,
                    placeholder: 'Pilih peserta rapat',
                    allowClear: true
                });
            });
        }

        // Modal Tambah
        $('#modalTambahRapat')
          .on('shown.bs.modal', function(){ initSelect2In($(this)); })
          .on('hidden.bs.modal', function(){
              var $sel = $(this).find('.js-example-basic-multiple');
              if ($sel.data('select2')) $sel.select2('destroy');
          });

        // Semua Modal Edit
        $('[id^="modalEditRapat-"]')
          .on('shown.bs.modal', function(){ initSelect2In($(this)); })
          .on('hidden.bs.modal', function(){
              var $sel = $(this).find('.js-example-basic-multiple');
              if ($sel.data('select2')) $sel.select2('destroy');
          });

        // Tooltip (opsional)
        $('[data-toggle="tooltip"]').tooltip();
    });
    </script>

    {{-- pastikan dropdown Select2 selalu di atas modal backdrop --}}
    <style>
      .select2-container .select2-dropdown { z-index: 2000 !important; }
      .select2-container { width: 100% !important; }
    </style>

    @yield('script')
    @stack('scripts')
</body>
</html>
