<?php

namespace App\Services;

use App\Models\OtpLog;
use Illuminate\Support\Facades\Log;

class OtpService
{
    protected int $expiryMinutes = 10;

    public function generate(string $mobile): string
    {
        if (config('site.demo_mode')) {
            $otp = '1234';
        } else {
            $otp = (string) random_int(100000, 999999);
        }

        // Purane OTPs expire karo
        OtpLog::where('mobile', $mobile)
               ->update(['used' => true]);

        // Naya OTP store karo
        OtpLog::create([
            'mobile' => $mobile,
            'otp'    => $otp,
            'expiry' => now()->addMinutes($this->expiryMinutes),
            'used'   => false,
        ]);

        return $otp;
    }

    public function verify(string $mobile, string $otp): bool
    {
        // Demo mode
        if (config('site.demo_mode') && $otp === '1234') {
            return true;
        }

        $record = OtpLog::where('mobile', $mobile)
            ->where('otp', $otp)
            ->where('used', false)
            ->where('expiry', '>', now())
            ->latest('id')
            ->first();

        if (!$record) return false;

        $record->update(['used' => true]);
        return true;
    }

    public function send(string $mobile, string $otp): bool
    {
        if (config('site.demo_mode')) {
            Log::info("DEMO OTP for {$mobile}: {$otp}");
            return true;
        }

        // TODO: SMS API integrate karo
        return true;
    }

    public function generateAndSend(string $mobile): bool
    {
        $otp = $this->generate($mobile);
        return $this->send($mobile, $otp);
    }
}
