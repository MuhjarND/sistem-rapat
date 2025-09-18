@extends('layouts.app')
@section('content')
<div class="container">
  <h4>Approval {{ ucfirst($req->doc_type) }}</h4>
  @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
  <div class="card"><div class="card-body">
    <p><b>Rapat:</b> {{ $req->judul }} ({{ $req->nomor_undangan ?? '-' }})</p>
    <p><b>Tanggal:</b> {{ \Carbon\Carbon::parse($req->tanggal)->format('d M Y') }} {{ $req->waktu_mulai }}</p>
    <p><b>Tempat:</b> {{ $req->tempat }}</p>
    <p><b>Urutan:</b> Tahap {{ $req->order_index }}</p>
    @if($blocked)
      <div class="alert alert-warning">Tahap sebelum Anda belum selesai. Silakan tunggu.</div>
    @else
      <form method="POST" action="{{ route('approval.sign.submit', $req->sign_token) }}">
        @csrf
        <button class="btn btn-success" type="submit">Setujui & Generate QR</button>
        <a href="{{ route('approval.pending') }}" class="btn btn-secondary">Kembali</a>
      </form>
    @endif
  </div></div>
</div>
@endsection
