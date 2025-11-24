@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">Buat Jadwal Rapat Berkala</h3>
            <div class="text-muted">Bulanan / Triwulanan / Tahunan — tidak otomatis masuk approval.</div>
        </div>
        <a href="{{ route('rapat.index') }}" class="btn btn-outline-secondary btn-sm">Kembali ke Daftar Rapat</a>
    </div>

    <div class="card">
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('rapat.schedule.store') }}" method="POST" autocomplete="off">
                @csrf
                @include('rapat._form', [
                    'rapat' => null,
                    'peserta_terpilih' => [],
                    'daftar_kategori' => $daftar_kategori,
                    'approval1_list' => $approval1_list,
                    'approval2_list' => $approval2_list,
                    'daftar_peserta' => $daftar_peserta,
                    'dropdownParentId' => null,
                    'pesertaWrapperId' => 'peserta-wrapper-jadwal',
                    'daftar_unit' => $daftar_unit,
                    'daftar_bidang' => $daftar_bidang,
                    'show_schedule' => true,
                ])

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted small">
                        Rapat yang dibuat di sini akan tampil di daftar rapat dengan status belum dikirim ke approval.
                    </div>
                    <div>
                        <a href="{{ route('rapat.index') }}" class="btn btn-outline-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Jadwal</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
