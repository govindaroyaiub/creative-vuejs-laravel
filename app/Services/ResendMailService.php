<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ResendMailService
{
    public static function send($toEmail, $toName, $subject, $htmlContent)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('RESEND_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://api.resend.com/emails', [
            'from' => 'Planet Nine <yourname@resend.dev>',
            'to' => [$toEmail],
            'subject' => $subject,
            'html' => $htmlContent,
        ]);

        return $response->json();
    }
}
