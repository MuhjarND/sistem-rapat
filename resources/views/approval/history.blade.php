{{-- resources/views/approval/history.blade.php (ringkas, ada pagination) --}}
@extends('layouts.app')
@section('title','Riwayat TTD Saya')
@section('content')
<div class="container">
  <h4 class="mb-3">Riwayat TTD Saya</h4>
  <div class="card">
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <thead>
          <tr class="text-center">
            <th style="width:60px">#</th>
            <th>Nomor Undangan</th>
            <th>Judul &amp; Tipe</th>
            <th>Tanggal &amp; Tempat</th>
            <th style="width:160px">Ditandatangani</th>
          </tr>
        </thead>
        <tbody>
        @forelse($rows as $i=>$r)
          <tr>
            <td class="text-center">{{ ($rows->currentPage()-1)*$rows->perPage()+$i+1 }}</td>
            <td>{{ $r->nomor_undangan ?? '-' }}</td>
            <td>
              <div class="font-weight-bold">{{ $r->judul }}</div>
              <div class="text-muted" style="font-size:12px">{{ ucfirst($r->doc_type) }}</div>
            </td>
            <td>
              {{ \Carbon\Carbon::parse($r->tanggal)->format('d M Y') }}
              <div class="text-muted" style="font-size:12px">{{ $r->tempat }}</div>
            </td>
            <td class="text-center">{{ $r->signed_at ? \Carbon\Carbon::parse($r->signed_at)->format('d M Y H:i') : '-' }}</td>
          </tr>
        @empty
          <tr><td colspan="5" class="text-center text-muted p-4">Belum ada riwayat.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
  <div class="mt-3">{{ $rows->links() }}</div>
</div>
@endsection
