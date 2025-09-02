@component('mail::message')
Yth. {{ $peserta->name }} ({{ $peserta->jabatan ?? '-' }})

Anda diundang dalam rapat berikut:

**Judul:** {{ $rapat->judul }}

**Tanggal:** {{ \Carbon\Carbon::parse($rapat->tanggal)->isoFormat('dddd, D MMMM Y') }}

**Waktu:** {{ $rapat->waktu_mulai }} WIT s/d selesai

**Tempat:** {{ $rapat->tempat }}

Silakan unduh dan baca lampiran undangan resmi.

Terima kasih.

@endcomponent
