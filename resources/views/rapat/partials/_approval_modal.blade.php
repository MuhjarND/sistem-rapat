﻿{{-- Modal status approval (dipakai desktop & mobile) --}}
@php
  // Guard: variabel yang dibutuhkan dari parent
  // $modalId, $overall, $approvalMap, $stepIcon, $stepClass, $popBadgeClass, $rapat

  // Hitung ulang overall dari semua baris approval (fallback jika $overall belum akurat)
  $overall = 'pending';
  // Jika kedua dokumen sudah approved (kolom di rapat), langsung approved
  if (!empty($rapat->undangan_approved_at ?? null) && !empty($rapat->absensi_approved_at ?? null)) {
    $overall = 'approved';
  } else {
    $allRows = collect($approvalMap)->flatten(1);
    if ($allRows->contains(fn($s) => ($s['status'] ?? null) === 'rejected')) {
      $overall = 'rejected';
    } elseif ($allRows->count() && $allRows->every(fn($s) => ($s['status'] ?? null) === 'approved')) {
      $overall = 'approved';
    }
  }

  // Cek apakah ada penolakan pada salah satu doc type
  $hasReject =
    collect($approvalMap['undangan'] ?? [])->contains(fn($s)=>($s['status'] ?? null)==='rejected') ||
    collect($approvalMap['absensi']  ?? [])->contains(fn($s)=>($s['status'] ?? null)==='rejected');

  // Helper label overall
  $overallText = $overall === 'approved' ? 'Semua Disetujui' : ($overall === 'rejected' ? 'Ada Penolakan' : 'Menunggu/Pending');
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content modal-solid">
      <div class="modal-header">
        <h5 class="modal-title">Status Approval — {{ $rapat->judul ?? 'Rapat' }}</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>

      <div class="modal-body">
        {{-- Ringkasan overall --}}
        <div class="mb-3">
          <span class="badge {{ $popBadgeClass($overall) }}">{{ $overallText }}</span>
        </div>

        {{-- Ringkas per dokumen (step chips) --}}
        <div class="mb-2">
          <div class="text-muted mb-1">Ringkas</div>

          @foreach (['undangan','absensi'] as $tp)
            @php $rows = $approvalMap[$tp] ?? []; @endphp
            <div class="mb-1">
              <b class="text-light text-uppercase" style="font-size:.8rem">{{ $tp }}</b>
            </div>
            @if(count($rows))
              @foreach($rows as $st)
                <span class="{{ $stepClass($st['status'] ?? 'pending') }}"
                      title="{{ ($st['name'] ?? 'Approver') }} - Step {{ $st['order'] ?? '?' }} - {{ ucfirst($st['status'] ?? 'pending') }}">
                  <b>{{ $stepIcon($st['status'] ?? 'pending') }}</b>
                  Step {{ $st['order'] ?? '?' }}
                </span>
              @endforeach
            @else
              <div class="muted">Belum ada konfigurasi approval.</div>
            @endif
          @endforeach
        </div>

        {{-- Tabel detail per dokumen --}}
        @foreach (['undangan'=>'Undangan','absensi'=>'Absensi'] as $tpKey => $tpTitle)
          @php $rows = $approvalMap[$tpKey] ?? []; @endphp
          <hr>
          <h6 class="mb-2">{{ $tpTitle }}</h6>
          @if(count($rows))
            <div class="table-responsive">
              <table class="table table-sm mini">
                <thead>
                  <tr>
                    <th class="nowrap" style="width:8%">Step</th>
                    <th style="width:28%">Approver</th>
                    <th style="width:12%">Status</th>
                    <th style="width:20%">Waktu</th>
                    <th class="w-notes">Catatan Penolakan</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($rows as $st)
                    @php
                      $status = $st['status'] ?? 'pending';
                      $when   = $status==='approved' ? ($st['signed_at'] ?? null)
                               : ($status==='rejected' ? ($st['rejected_at'] ?? null) : null);
                    @endphp
                    <tr>
                      <td class="nowrap">#{{ $st['order'] ?? '?' }}</td>
                      <td>{{ $st['name'] ?? 'Approver' }}</td>
                      <td class="nowrap">
                        @if($status==='approved')
                          <span class="badge badge-success">Approved</span>
                        @elseif($status==='rejected')
                          <span class="badge badge-danger">Rejected</span>
                        @elseif($status==='blocked')
                          <span class="badge badge-secondary">Blocked</span>
                        @else
                          <span class="badge badge-warning">Pending</span>
                        @endif
                      </td>
                      <td class="nowrap">
                        @if($when)
                          {{ \Carbon\Carbon::parse($when)->translatedFormat('d M Y H:i') }}
                        @else
                          -
                        @endif
                      </td>
                      <td>
                        @if($status==='rejected' && !empty($st['rejection_note']))
                          {{ $st['rejection_note'] }}
                        @else
                          <span class="muted">-</span>
                        @endif
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @else
            <div class="muted">Belum ada konfigurasi approval.</div>
          @endif
        @endforeach
      </div>

      <div class="modal-footer">
        @if($hasReject)
          <button type="button"
                  class="btn btn-danger btn-sm"
                  onclick="openEditFromApproval('{{ $modalId }}','modalEditRapat-{{ $rapat->id }}')">
            <i class="fas fa-tools mr-1"></i> Perbaiki
          </button>
        @endif
        <button class="btn btn-outline-light btn-sm" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>



