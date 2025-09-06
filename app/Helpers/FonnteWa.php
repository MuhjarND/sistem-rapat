<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteWa
{
    public static function send($to, $message)
    {
        $token = env('FONNTE_TOKEN');
        $endpoint = 'https://api.fonnte.com/send';

        $response = Http::withHeaders([
            'Authorization' => $token,
        ])->post($endpoint, [
            'target' => $to, // Format: 6281234567890
            'message' => $message,
        ]);

        Log::info('Fonnte WA: '.$response->body());

        return $response->json();
    }
}
