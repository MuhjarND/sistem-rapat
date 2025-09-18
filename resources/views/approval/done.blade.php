@extends('layouts.app')
@section('content')
<div class="container">
  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  <div class="card"><div class="card-body">
    <h4>Approval Selesai</h4>
    <p>Dokumen {{ ucfirst($req->doc_type) }} untuk rapat <b>{{ $rapat->judul }}</b> telah disetujui.</p>
    <a href="{{ route('approval.pending') }}" class="btn btn-primary">Kembali ke Approval Saya</a>
  </div></div>
</div>
@endsection
