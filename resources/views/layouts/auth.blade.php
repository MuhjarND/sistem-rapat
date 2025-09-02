<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Login - Sistem Manajemen Rapat</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background: #101820;
            min-height: 100vh;
            position: relative;
        }
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 6px 32px rgba(0,0,0,0.16), 0 1.5px 5px rgba(16,24,32,.11);
            padding: 38px 32px 30px 32px;
            max-width: 360px;
            width: 100%;
            position: relative;
        }
        .login-title {
            color: #101820;
            font-size: 22px;
            text-align: center;
            margin-bottom: 24px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .login-logo {
            display: block;
            margin: 0 auto 18px auto;
            background: #101820;
            color: #FEE715;
            border-radius: 50%;
            padding: 18px 18px 10px 18px;
            width: 74px;
            height: 74px;
            text-align: center;
            box-shadow: 0 3px 16px rgba(16,24,32,0.09);
        }
        .login-logo i {
            font-size: 38px;
            color: #FEE715;
        }
        .btn-login {
            background: #FEE715;
            color: #101820;
            font-weight: bold;
            border: none;
            box-shadow: 0 1.5px 5px rgba(254,231,21,.13);
        }
        .btn-login:hover, .btn-login:focus {
            background: #fff700;
            color: #000;
        }
        .login-footer {
            text-align: center;
            font-size: 12px;
            color: #c8c8c8;
            margin-top: 30px;
        }
        .form-control:focus {
            border-color: #FEE715;
            box-shadow: 0 0 0 0.08rem #FEE7156e;
        }
    </style>
    @yield('style')
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-logo">
                <i class="fas fa-users-cog"></i>
            </div>
            <div class="login-title">
                Selamat Datang<br>
                <span style="font-size: 13px; font-weight:normal;">Sistem Manajemen Rapat</span>
            </div>
            @yield('content')
            <div class="login-footer mt-3">
                &copy; {{ date('Y') }} - Manajemen Rapat
            </div>
        </div>
    </div>
    <script src="https://kit.fontawesome.com/6ee476ff68.js" crossorigin="anonymous"></script>
</body>
</html>
