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

        // Log the full response
        \Log::info('Resend API response', $response->json());

        return $response->json();
    }
}
