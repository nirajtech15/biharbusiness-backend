<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function sendOtp(string $mobile, string $otp): array
    {
        $driver = config('sms.driver', 'log');

        if ($driver === 'fast2sms') {
            return $this->sendViaFast2Sms($mobile, $otp);
        }

        if ($driver === 'msg91') {
            return $this->sendViaMsg91($mobile, $otp);
        }

        // default log/testing mode
        Log::info("OTP for {$mobile}: {$otp}");

        return [
            'success' => true,
            'message' => "Test OTP: {$otp}",
        ];
    }

    private function sendViaFast2Sms(string $mobile, string $otp): array
    {
        $apiKey = config('sms.fast2sms_api_key');

        if (!$apiKey) {
            return [
                'success' => false,
                'message' => 'FAST2SMS API key missing.',
            ];
        }

        $response = Http::withHeaders([
            'authorization' => $apiKey,
            'accept' => 'application/json',
        ])->post('https://www.fast2sms.com/dev/bulkV2', [
            'route' => config('sms.route', 'q'),
            'variables_values' => $otp,
            'flash' => 0,
            'numbers' => $mobile,
            'message' => "Your BiharBusiness OTP is {$otp}",
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'message' => 'OTP sent successfully.',
                'response' => $response->json(),
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to send OTP via Fast2SMS.',
            'response' => $response->body(),
        ];
    }

    private function sendViaMsg91(string $mobile, string $otp): array
    {
        $authKey = config('sms.msg91_auth_key');

        if (!$authKey) {
            return [
                'success' => false,
                'message' => 'MSG91 auth key missing.',
            ];
        }

        $response = Http::get('https://api.msg91.com/api/sendhttp.php', [
            'authkey' => $authKey,
            'mobiles' => $mobile,
            'message' => "Your BiharBusiness OTP is {$otp}",
            'sender' => config('sms.sender_id', 'BIHBIZ'),
            'route' => 4,
            'country' => 91,
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'message' => 'OTP sent successfully.',
                'response' => $response->body(),
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to send OTP via MSG91.',
            'response' => $response->body(),
        ];
    }
}
