@extends('layouts.auth')
@section('title','Login')

@section('content')
{{-- === ALERT AREA === --}}
@if (session('error'))
    <div class="alert alert-danger text-center py-2 small fade show" role="alert">
        <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
    </div>
@endif

@if (session('status'))
    <div class="alert alert-success text-center py-2 small fade show" role="alert">
        <i class="fas fa-check-circle mr-1"></i> {{ session('status') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-warning text-center py-2 small fade show" role="alert">
        <i class="fas fa-exclamation-triangle mr-1"></i> Terjadi kesalahan, periksa kembali input Anda.
    </div>
@endif

<form method="POST" action="{{ route('login') }}" onsubmit="handleSubmitting(this)">
    @csrf

    {{-- Email --}}
    <div class="form-group">
        <label class="form-label" for="email">Email</label>
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
            </div>
            <input type="email" id="email" name="email"
                   class="form-control @error('email') is-invalid @enderror"
                   placeholder="nama@pta-papuabarat.go.id"
                   value="{{ old('email') }}" required autofocus>
            @error('email')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Password --}}
    <div class="form-group">
        <label class="form-label" for="password">Kata Sandi</label>
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
            </div>
            <input type="password" id="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   placeholder="••••••••" required>
            <div class="input-group-append">
                <button class="btn btn-outline-secondary" type="button" id="togglePass"
                        style="border-color:#333; color:#cbd5e1; background:#0b1220">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
        <div id="capsNote" class="caps-indicator"><i class="fas fa-exclamation-triangle mr-1"></i>Caps Lock aktif</div>
    </div>

    {{-- Remember Me --}}
    <div class="form-group form-check mb-3">
        <input type="checkbox" name="remember" class="form-check-input" id="remember" {{ old('remember') ? 'checked' : '' }}>
        <label class="form-check-label" for="remember">Ingat saya</label>
    </div>

    {{-- Submit --}}
    <button type="submit" class="btn btn-login btn-block" id="btnLogin">
        <span class="spinner-border spinner-border-sm d-none" id="spin"></span>
        <span id="btnText">Masuk</span>
    </button>
</form>
@endsection

@section('script')
<script>
    // toggle show/hide password
    document.getElementById('togglePass').addEventListener('click', function(){
        const inp = document.getElementById('password');
        const icon = this.querySelector('i');
        if (inp.type === 'password') {
            inp.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            inp.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });

    // caps lock indicator
    const pwd = document.getElementById('password');
    const note = document.getElementById('capsNote');
    pwd.addEventListener('keyup', e => {
        note.style.display = e.getModifierState('CapsLock') ? 'block' : 'none';
    });

    // disable on submit + spinner
    function handleSubmitting(form){
        document.getElementById('btnLogin').disabled = true;
        document.getElementById('spin').classList.remove('d-none');
        document.getElementById('btnText').innerText = 'Memproses...';
        return true;
    }

    // auto-hide alert after few seconds
    $(function(){
        setTimeout(()=>{$('.alert').fadeOut('slow');}, 3500);
    });
</script>
@endsection
