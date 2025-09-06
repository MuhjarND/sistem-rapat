<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class UndanganRapatMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $rapatId;
    protected $pesertaId;

    public function __construct($rapatId, $pesertaId)
    {
        $this->rapatId = $rapatId;
        $this->pesertaId = $pesertaId;
    }

    public function build()
    {
        // Ambil ulang data via Query Builder
        $rapat = DB::table('rapat')->where('id', $this->rapatId)->first();
        $peserta = DB::table('users')->where('id', $this->pesertaId)->first();
        $pimpinan = DB::table('pimpinan_rapat')->where('id', $rapat->id_pimpinan)->first();

        $pdf = Pdf::loadView('rapat.undangan_pdf', [
            'rapat' => $rapat,
            'daftar_peserta' => [$peserta],
            'pimpinan' => $pimpinan,
            'kop_path' => public_path('Screenshot 2025-08-23 121254.jpeg')
        ])->setPaper('A4', 'portrait');

        $pdfData = $pdf->output();

    return $this->subject('Undangan Rapat: '.$rapat->judul)
        // PENTING: kirim $rapat dan $peserta ke view
        ->markdown('emails.undangan_rapat', [
            'rapat' => $rapat,
            'peserta' => $peserta,
            // tambah variabel lain jika perlu
        ])
        ->attachData($pdfData, 'Undangan-Rapat.pdf', [
            'mime' => 'application/pdf',
        ]);
    }
}

