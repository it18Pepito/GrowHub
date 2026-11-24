<?php

namespace App\Services;

use App\Mail\PasswordOtpMail;
use App\Models\MailSetting;
use App\Models\PasswordChangeOtp;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordOtpService
{
    public const EXPIRES_MINUTES = 1;
    public const RESEND_COOLDOWN_SECONDS = 60;
    public const MAX_ATTEMPTS = 5;

    /**
     * Generate, persist, and email a 6-digit OTP to the user's registered email.
     */
    public function sendOtp(User $user, string $purpose = 'first_change_password'): array
    {
        if (empty($user->email)) {
            throw ValidationException::withMessages([
                'email' => 'Akun Anda belum memiliki alamat email terdaftar. Silakan hubungi Administrator untuk melengkapi data email Anda terlebih dahulu.',
            ]);
        }

        $latest = PasswordChangeOtp::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if ($latest && $latest->created_at->gt(now()->subSeconds(self::RESEND_COOLDOWN_SECONDS))) {
            $elapsed = max(0, now()->timestamp - $latest->created_at->timestamp);
            $retryAfter = max(1, self::RESEND_COOLDOWN_SECONDS - $elapsed);

            throw new HttpResponseException(response()->json([
                'message' => "Kode OTP sudah dikirim. Silakan tunggu {$retryAfter} detik sebelum meminta kode baru.",
                'retry_after' => $retryAfter,
                'masked_email' => $this->maskEmail($user->email),
            ], 429));
        }

        // Remove unused previous OTPs for this purpose
        PasswordChangeOtp::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->delete();

        $otpCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $record = PasswordChangeOtp::create([
            'user_id' => $user->id,
            'purpose' => $purpose,
            'email' => $user->email,
            'code_hash' => Hash::make($otpCode),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(self::EXPIRES_MINUTES),
        ]);

        try {
            MailSetting::current()->applyRuntimeConfig();
            Mail::to($user->email)->send(new PasswordOtpMail($user, $otpCode, self::EXPIRES_MINUTES));
        } catch (\Throwable $e) {
            $record->delete();
            Log::error('Failed to send password change OTP email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            throw new HttpResponseException(response()->json([
                'message' => 'Gagal mengirim kode OTP ke email terdaftar. Periksa konfigurasi email server atau hubungi Administrator.',
            ], 500));
        }

        return [
            'message' => 'Kode OTP telah dikirim ke email terdaftar Anda.',
            'masked_email' => $this->maskEmail($user->email),
            'expires_in' => self::EXPIRES_MINUTES * 60,
            'resend_cooldown' => self::RESEND_COOLDOWN_SECONDS,
        ];
    }

    /**
     * Verify the 6-digit OTP and issue a short-lived verification token for the password change step.
     */
    public function verifyOtp(User $user, string $otpCode, string $purpose = 'first_change_password'): string
    {
        $record = PasswordChangeOtp::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if (! $record) {
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP belum dikirim atau sudah tidak berlaku. Silakan kirim ulang kode OTP.',
            ]);
        }

        if ($record->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP telah kedaluwarsa. Silakan kirim ulang kode OTP baru.',
            ]);
        }

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            throw ValidationException::withMessages([
                'otp' => 'Terlalu banyak percobaan OTP salah. Silakan kirim ulang kode OTP baru.',
            ]);
        }

        if (! Hash::check($otpCode, $record->code_hash)) {
            $record->increment('attempts');
            $remaining = max(0, self::MAX_ATTEMPTS - $record->attempts);

            $msg = $remaining > 0
                ? "Kode OTP tidak sesuai. Sisa percobaan: {$remaining} kali."
                : 'Terlalu banyak percobaan OTP salah. Silakan kirim ulang kode OTP baru.';

            throw ValidationException::withMessages(['otp' => $msg]);
        }

        $verificationToken = Str::random(64);
        $minDeadline = now()->addMinutes(5);

        $record->update([
            'verified_at' => now(),
            'verification_token_hash' => Hash::make($verificationToken),
            'expires_at' => $record->expires_at->gt($minDeadline) ? $record->expires_at : $minDeadline,
        ]);

        return $verificationToken;
    }

    /**
     * Validate the OTP verification token right before changing the user's password.
     */
    public function validateVerifiedToken(User $user, string $verificationToken, string $purpose = 'first_change_password'): PasswordChangeOtp
    {
        $record = PasswordChangeOtp::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->whereNotNull('verified_at')
            ->whereNotNull('verification_token_hash')
            ->latest('id')
            ->first();

        if (! $record || $record->expires_at->isPast() || ! Hash::check($verificationToken, $record->verification_token_hash)) {
            throw ValidationException::withMessages([
                'otp_token' => 'Sesi verifikasi OTP tidak valid atau telah kedaluwarsa. Silakan verifikasi ulang kode OTP Anda.',
            ]);
        }

        return $record;
    }

    public function maskEmail(string $email): string
    {
        if (! str_contains($email, '@')) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);
        $len = strlen($local);

        if ($len <= 2) {
            $maskedLocal = substr($local, 0, 1).str_repeat('*', max(1, $len - 1));
        } else {
            $maskedLocal = substr($local, 0, 2).str_repeat('*', min(5, $len - 2));
        }

        return "{$maskedLocal}@{$domain}";
    }
}
