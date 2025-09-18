@extends('layouts.app')
@section('content')
<div class="container">
  <h4>Approval Saya</h4>
  <div class="card">
    <div class="card-body p-0">
      <table class="table table-striped m-0">
        <thead>
          <tr>
            <th>Dokumen</th>
            <th>Rapat</th>
            <th>Nomor</th>
            <th>Tanggal</th>
            <th>Tempat</th>
            <th>Urutan</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $r)
          <tr>
            <td>{{ ucfirst($r->doc_type) }}</td>
            <td>{{ $r->judul }}</td>
            <td>{{ $r->nomor_undangan ?? '-' }}</td>
            <td>{{ \Carbon\Carbon::parse($r->tanggal)->format('d M Y') }} {{ $r->waktu_mulai }}</td>
            <td>{{ $r->tempat }}</td>
            <td>{{ $r->order_index }}</td>
            <td>
              @if($r->blocked)
                <span class="badge badge-secondary">Menunggu tahap sebelum</span>
              @else
                <a class="btn btn-primary btn-sm" href="{{ route('approval.sign', $r->sign_token) }}">
                  Review & Setujui
                </a>
              @endif
            </td>
          </tr>
          @empty
          <tr><td colspan="7" class="text-center">Tidak ada approval pending.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
