<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\TestSmtpMail;
use App\Models\MailSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AdminMailSettingController extends Controller
{
    /**
     * Tampilkan konfigurasi SMTP terkini untuk Admin.
     */
    public function index(): JsonResponse
    {
        $setting = MailSetting::current();

        return response()->json([
            'setting' => [
                'mail_mailer' => $setting->mail_mailer ?: 'smtp',
                'mail_scheme' => $setting->mail_scheme,
                'mail_host' => $setting->mail_host,
                'mail_port' => (int) $setting->mail_port,
                'mail_username' => $setting->mail_username,
                'has_password' => ! empty($setting->mail_password),
                'mail_from_address' => $setting->mail_from_address,
                'mail_from_name' => $setting->mail_from_name,
                'updated_at' => $setting->updated_at?->toIso8601String(),
                'updated_by_name' => $setting->updater?->name,
            ],
            'env_snapshot' => [
                'MAIL_MAILER' => env('MAIL_MAILER', 'smtp'),
                'MAIL_SCHEME' => env('MAIL_SCHEME', 'smtps'),
                'MAIL_HOST' => env('MAIL_HOST', 'smtp-1.rcoid.com'),
                'MAIL_PORT' => (int) env('MAIL_PORT', 465),
                'MAIL_USERNAME' => env('MAIL_USERNAME', 'noreply.pepito@rcoid.com'),
                'MAIL_FROM_ADDRESS' => env('MAIL_FROM_ADDRESS', 'noreply.pepito@rcoid.com'),
                'MAIL_FROM_NAME' => env('MAIL_FROM_NAME', 'GrowHub- GrowHub'),
            ],
        ]);
    }

    /**
     * Simpan pembaruan nilai konfigurasi SMTP di database dan file .env.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mail_mailer' => ['required', 'string', 'in:smtp,sendmail,log,array'],
            'mail_scheme' => ['nullable', 'string', 'in:smtps,smtp,tls,ssl,null'],
            'mail_host' => ['required', 'string', 'max:190'],
            'mail_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['nullable', 'string', 'max:190'],
            'mail_password' => ['nullable', 'string', 'max:190'],
            'mail_from_address' => ['required', 'email', 'max:190'],
            'mail_from_name' => ['required', 'string', 'max:190'],
        ]);

        $setting = MailSetting::current();

        // Jika password tidak diinput, pertahankan password yang lama
        $passwordToSave = ! empty($validated['mail_password'])
            ? $validated['mail_password']
            : $setting->mail_password;

        $schemeToSave = ($validated['mail_scheme'] === 'null' || empty($validated['mail_scheme']))
            ? null
            : $validated['mail_scheme'];

        // 1. Simpan ke database
        $setting->update([
            'mail_mailer' => $validated['mail_mailer'],
            'mail_scheme' => $schemeToSave,
            'mail_host' => $validated['mail_host'],
            'mail_port' => (int) $validated['mail_port'],
            'mail_username' => $validated['mail_username'] ?: null,
            'mail_password' => $passwordToSave,
            'mail_from_address' => $validated['mail_from_address'],
            'mail_from_name' => $validated['mail_from_name'],
            'updated_by' => $request->user()?->id,
        ]);

        // 2. Perbarui baris konfigurasi pada file .env (L50-L57)
        $envData = [
            'MAIL_MAILER' => $validated['mail_mailer'],
            'MAIL_SCHEME' => $schemeToSave ?: '',
            'MAIL_HOST' => $validated['mail_host'],
            'MAIL_PORT' => $validated['mail_port'],
            'MAIL_USERNAME' => $validated['mail_username'] ?: '',
            'MAIL_FROM_ADDRESS' => $validated['mail_from_address'],
            'MAIL_FROM_NAME' => $validated['mail_from_name'],
        ];

        if (! empty($passwordToSave)) {
            $envData['MAIL_PASSWORD'] = $passwordToSave;
        }

        $envUpdated = $this->updateEnvFile($envData);

        // 3. Update runtime in-memory config
        $this->applyRuntimeMailConfig($setting);

        // 4. Bersihkan cache config agar Laravel mengenali nilai baru
        try {
            Artisan::call('config:clear');
        } catch (\Throwable $e) {
            Log::warning('Config clear error on mail update: '.$e->getMessage());
        }

        return response()->json([
            'message' => 'Konfigurasi SMTP berhasil disimpan dan file .env telah diperbarui.',
            'env_updated' => $envUpdated,
            'setting' => [
                'mail_mailer' => $setting->mail_mailer,
                'mail_scheme' => $setting->mail_scheme,
                'mail_host' => $setting->mail_host,
                'mail_port' => (int) $setting->mail_port,
                'mail_username' => $setting->mail_username,
                'has_password' => ! empty($setting->mail_password),
                'mail_from_address' => $setting->mail_from_address,
                'mail_from_name' => $setting->mail_from_name,
                'updated_at' => $setting->fresh()->updated_at?->toIso8601String(),
                'updated_by_name' => $request->user()?->name,
            ],
        ]);
    }

    /**
     * Kirim email pengujian ke alamat tujuan untuk memverifikasi koneksi SMTP.
     */
    public function sendTestMail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipient_email' => ['required', 'email', 'max:190'],
        ]);

        $setting = MailSetting::current();
        $this->applyRuntimeMailConfig($setting);

        $adminUser = $request->user();
        $testedBy = $adminUser ? "{$adminUser->name} (NIP: {$adminUser->nip})" : 'Administrator';
        
        $tz = config('app.timezone', 'Asia/Makassar');
        $tzAbbr = match ($tz) {
            'Asia/Jakarta' => 'WIB',
            'Asia/Jayapura' => 'WIT',
            default => 'WITA',
        };
        $testedAt = now()->setTimezone($tz)->locale('id')->translatedFormat('d F Y, H:i:s')." {$tzAbbr}";

        $smtpDetails = [
            'mailer' => $setting->mail_mailer,
            'scheme' => $setting->mail_scheme ?: 'none',
            'host' => $setting->mail_host,
            'port' => $setting->mail_port,
            'username' => $setting->mail_username,
            'from_address' => $setting->mail_from_address,
            'from_name' => $setting->mail_from_name,
        ];

        try {
            Mail::to($validated['recipient_email'])->send(new TestSmtpMail(
                testedBy: $testedBy,
                testedAt: $testedAt,
                smtpDetails: $smtpDetails,
            ));

            return response()->json([
                'success' => true,
                'message' => "Email pengujian berhasil dikirim ke {$validated['recipient_email']}!",
            ]);
        } catch (\Throwable $e) {
            Log::error('SMTP Test Mail Failed', [
                'recipient' => $validated['recipient_email'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim email pengujian: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Terapkan konfigurasi SMTP ke runtime Laravel memory.
     */
    private function applyRuntimeMailConfig(MailSetting $setting): void
    {
        $setting->applyRuntimeConfig();
    }

    /**
     * Tulis pembaruan nilai ke file .env di backend.
     */
    private function updateEnvFile(array $values): bool
    {
        $envPath = base_path('.env');
        if (! file_exists($envPath)) {
            return false;
        }

        $content = file_get_contents($envPath);

        foreach ($values as $key => $value) {
            $valStr = (string) $value;
            // Bungkus dalam tanda kutip jika mengandung spasi atau karakter khusus
            if (preg_match('/\s|[#$"\\\\]/', $valStr)) {
                $formatted = '"'.addcslashes($valStr, '"\\$').'"';
            } elseif ($valStr === '') {
                $formatted = '';
            } else {
                $formatted = $valStr;
            }

            $pattern = "/^{$key}=.*/m";
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, "{$key}={$formatted}", $content);
            } else {
                $content .= "\n{$key}={$formatted}";
            }
        }

        return file_put_contents($envPath, $content) !== false;
    }
}
