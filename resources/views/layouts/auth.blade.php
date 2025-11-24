<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>@yield('title','Login') - Sistem Manajemen Rapat</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('logo_app.png') }}">

    {{-- Bootstrap 4 --}}
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    {{-- Font Awesome (CSS CDN) --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root{
            --bg:#101820;
            --card:#0f172a;
            --border: rgba(255,255,255,.1);
            --text:#e5e7eb;
            --accent:#FEE715;
        }
        body{
            background: var(--bg);
            color: var(--text);
            min-height:100vh;
            display:flex; align-items:center; justify-content:center;
        }
        .login-card{
            width:100%; max-width:380px;
            background:#111827;
            border-radius:14px;
            border:1px solid var(--border);
            padding:28px 26px 20px 26px;
            box-shadow:0 12px 40px rgba(0,0,0,.3);
        }
        .login-logo{
            width:120px; height:120px;
            border-radius:50%; margin:-90px auto 18px auto;
            display:flex; align-items:center; justify-content:center;
            background: radial-gradient(circle at 50% 40%, rgba(254,231,21,.14), rgba(254,231,21,.08));
            border:1px dashed rgba(254,231,21,.45);
            box-shadow:0 14px 32px rgba(254,231,21,.18);
        }
        .login-logo img{ width:100%; height:100%; object-fit:contain; border-radius:50%; }
        .login-logo i{ font-size:40px; color:var(--accent) }
        .login-title{
            text-align:center; font-weight:700; font-size:22px; color:#fff;
            margin-bottom:10px;
        }
        .login-sub{ display:block; font-size:13px; color:#c7d2fe; font-weight:400 }
        .form-label{ font-weight:600; font-size:12.5px; color:#cbd5e1; margin-bottom:.35rem }
        .form-control{
            background:#0b1220; border:1px solid var(--border); color:#fff;
        }
        .form-control:focus{
            border-color:rgba(254,231,21,.55);
            box-shadow:0 0 0 .15rem rgba(254,231,21,.15);
        }
        .input-group-text{ background:#0b1220; border:1px solid var(--border); color:#cbd5e1 }
        .btn-login{
            background:var(--accent); color:#101820; font-weight:700; border:none;
            box-shadow:0 6px 24px rgba(254,231,21,.18);
        }
        .btn-login:hover{ background:#fff700; color:#000 }
        .login-footer{
            text-align:center; font-size:12px; color:#9fb0cd; margin-top:16px;
        }
        .caps-indicator{ display:none; font-size:12px; color:#fca5a5; margin-top:6px }
    </style>
    @yield('style')
</head>
<body>
    <div class="login-card">
        <div class="login-logo">
            <img src="{{ asset('logo_app.png') }}" alt="Logo Aplikasi">
        </div>
        <div class="login-title">
            Selamat Datang
            <span class="login-sub">Sistem Manajemen Rapat</span>
        </div>

        @yield('content')

        <div class="login-footer">
            &copy; {{ date('Y') }} • Manajemen Rapat
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    @yield('script')
</body>
</html>
