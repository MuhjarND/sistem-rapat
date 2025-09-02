<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UndanganRapatMail extends Mailable
{
    use Queueable, SerializesModels;

    public $rapat;
    public $peserta;
    public $pdfData;

    public function __construct($rapat, $peserta, $pdfData)
    {
        $this->rapat = $rapat;
        $this->peserta = $peserta;
        $this->pdfData = $pdfData;
    }

    public function build()
    {
        return $this->subject('Undangan Rapat: '.$this->rapat->judul)
            ->markdown('emails.undangan_rapat')
            ->attachData($this->pdfData, 'Undangan-Rapat.pdf', [
                'mime' => 'application/pdf',
            ]);
    }
}

