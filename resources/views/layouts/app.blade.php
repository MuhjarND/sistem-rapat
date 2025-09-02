<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Sistem Manajemen Rapat')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        body { background: #f7f7f7; }
        .sidebar {  
            min-height: 100vh;
            background: #152046;   /* NAVY */
            color: #e1ba96;        /* GOLD/TAN */
            padding-top: 30px;
        }
        .sidebar .nav-link {
            color: #e1ba96;
            font-weight: 500;
            margin-bottom: 6px;
            border-radius: 5px;
            transition: background 0.2s, color 0.2s;
        }
        .sidebar .nav-link.active, .sidebar .nav-link:hover {
            background: #e1ba96;
            color: #152046 !important;
        }
        .sidebar .nav-link i {
            width: 24px;
            text-align: center;
            margin-right: 7px;
        }
        .sidebar .sidebar-title {
            font-size: 18px;
            letter-spacing: 1px;
            margin-bottom: 22px;
            text-align: center;
            font-weight: bold;
            color: #fff;
            text-shadow: 0 1px 2px #152046;
        }
        .content-area { padding: 32px 28px; }
        .navbar { background: #152046; color: #e1ba96; }
        .navbar-brand { color: #e1ba96 !important; font-weight:bold; }

        /* submenu style */
        .submenu { margin-left: 28px; }
        .submenu .nav-link { margin-bottom: 4px; font-weight: 500; }

        /* badge notifikasi ala contoh */
        .badge-ping {
            background: #ff2d55;
            color: #fff;
            border-radius: 999px;
            font-size: 12px;
            padding: 3px 8px;
            line-height: 1;
        }
    </style>
    @yield('style')
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
        <a class="navbar-brand" href="{{ url('/') }}">
            <i class="fas fa-cogs"></i> Manajemen Rapat
        </a>
        <div class="ml-auto d-flex align-items-center">
            <span class="mr-3">{{ Auth::user()->name ?? '' }}</span>
            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                @csrf
                <button class="btn btn-sm btn-danger">Logout</button>
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

                    // buka-tutup "Laporan" (dropdown)
                    $openLaporan = request()->routeIs('laporan.baru') || request()->routeIs('laporan.arsip') || request()->is('laporan/*');

                    // hitung badge cepat (bulan berjalan vs arsip)
                    $nowY = date('Y'); $nowM = date('m');
                    $badgeBaru = \DB::table('laporan_files')
                                    ->whereYear('created_at',$nowY)
                                    ->whereMonth('created_at',$nowM)
                                    ->count();
                    $badgeArsip = \DB::table('laporan_files')
                                    ->where(function($q) use($nowY,$nowM){
                                        $q->whereYear('created_at','<',$nowY)
                                          ->orWhere(function($qq) use($nowY,$nowM){
                                              $qq->whereYear('created_at',$nowY)
                                                 ->whereMonth('created_at','<',$nowM);
                                          });
                                    })->count();
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
                    <a class="nav-link {{ request()->is('notulensi*') ? 'active' : '' }}" href="{{ route('notulensi.index') }}">
                        <i class="fas fa-book-open"></i> Notulensi
                    </a>

                    {{-- ================== Laporan (DROPDOWN) ================== --}}
                    <a class="nav-link d-flex align-items-center {{ $openLaporan ? '' : 'collapsed' }}"
                       data-toggle="collapse" href="#menuLaporan" role="button"
                       aria-expanded="{{ $openLaporan ? 'true' : 'false' }}" aria-controls="menuLaporan">
                        <i class="fas fa-folder-open"></i> Laporan
                        <i class="ml-auto fas fa-angle-down"></i>
                    </a>
                    <div class="collapse {{ $openLaporan ? 'show' : '' }}" id="menuLaporan">
                        <div class="nav flex-column submenu">
                            <a class="nav-link d-flex justify-content-between align-items-center {{ request()->routeIs('laporan.baru') ? 'active' : '' }}"
                               href="{{ route('laporan.baru') }}">
                                <span><i class="fas fa-circle mr-2" style="font-size:8px;"></i> Laporan Baru</span>
                                @if($badgeBaru>0)
                                  <span class="badge-ping">{{ $badgeBaru }}</span>
                                @endif
                            </a>
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

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    @yield('script')
</body>
</html>
