<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserOtp;

class OtpService
{
    /**
     * Generate a new OTP for the given user and context.
     */
    public function generate(User $user, string $context, int $ttlMinutes = 10): UserOtp
    {
        // Invalidate existing active OTPs for this context
        $user->otps()
            ->where('context', $context)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        return $user->otps()->create([
            'code' => $code,
            'context' => $context,
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);
    }

    /**
     * Validate the provided OTP code for the given user and context.
     */
    public function validate(User $user, string $context, string $code): bool
    {
        $otp = $user->otps()
            ->where('context', $context)
            ->whereNull('consumed_at')
            ->where('expires_at', '>=', now())
            ->orderByDesc('id')
            ->first();

        if (!$otp) {
            return false;
        }

        if (!hash_equals($otp->code, $code)) {
            return false;
        }

        $otp->update(['consumed_at' => now()]);

        return true;
    }
}
