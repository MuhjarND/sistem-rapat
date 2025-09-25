@extends('layouts.app')
@section('title','Notulensi Rapat')

@section('content')
<div class="card">
  <div class="card-header">
    <strong>Notulensi: {{ $rapat->judul }}</strong>
  </div>
  <div class="card-body">
    @forelse($detail as $i => $row)
      <div class="mb-3">
        <div class="text-muted mb-1">#{{ $row->urut }}</div>
        <div><b>Hasil:</b> {!! $row->hasil_pembahasan !!}</div>
        @if($row->rekomendasi)
          <div class="mt-1"><b>Rekomendasi:</b> {!! $row->rekomendasi !!}</div>
        @endif
        <div class="mt-1">
          <b>PJ:</b> {{ $row->penanggung_jawab ?? '-' }}
          <span class="ml-2"><b>Tgl Selesai:</b>
            {{ $row->tgl_penyelesaian ? \Carbon\Carbon::parse($row->tgl_penyelesaian)->isoFormat('D MMMM Y') : '-' }}
          </span>
        </div>
      </div>
      <hr>
    @empty
      <p class="text-muted">Belum ada detail notulensi.</p>
    @endforelse
  </div>
</div>
@endsection
