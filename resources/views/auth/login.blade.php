@extends('layouts.auth')

@section('content')
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
      <div class="alert alert-danger">
        <ul style="margin:0;padding-left:16px;">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
        </ul>
      </div>
    @endif
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="form-group">
            <label for="email" style="color:#101820;">Email</label>
            <input id="email" type="email" name="email" class="form-control" required autofocus value="{{ old('email') }}">
        </div>
        <div class="form-group">
            <label for="password" style="color:#101820;">Password</label>
            <input id="password" type="password" name="password" class="form-control" required>
        </div>
        <button class="btn btn-login btn-block mt-2 mb-2">Login</button>
    </form>
@endsection
