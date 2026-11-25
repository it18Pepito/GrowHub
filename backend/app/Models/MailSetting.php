<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailSetting extends Model
{
    protected $fillable = [
        'mail_mailer',
        'mail_scheme',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_from_address',
        'mail_from_name',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'mail_port' => 'integer',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function current(): self
    {
        $setting = static::first();
        if (! $setting) {
            $setting = static::create([
                'mail_mailer' => env('MAIL_MAILER', 'smtp'),
                'mail_scheme' => env('MAIL_SCHEME', 'smtps'),
                'mail_host' => env('MAIL_HOST', 'smtp-1.rcoid.com'),
                'mail_port' => (int) env('MAIL_PORT', 465),
                'mail_username' => env('MAIL_USERNAME', 'noreply.pepito@rcoid.com'),
                'mail_password' => env('MAIL_PASSWORD', 'zespuha5'),
                'mail_from_address' => env('MAIL_FROM_ADDRESS', 'noreply.pepito@rcoid.com'),
                'mail_from_name' => env('MAIL_FROM_NAME', 'GrowHub - Ramayana & Co'),
            ]);
        }

        return $setting;
    }

    public function applyRuntimeConfig(): void
    {
        config([
            'mail.default' => $this->mail_mailer ?: 'smtp',
            'mail.mailers.smtp.transport' => $this->mail_mailer ?: 'smtp',
            'mail.mailers.smtp.scheme' => $this->mail_scheme,
            'mail.mailers.smtp.host' => $this->mail_host,
            'mail.mailers.smtp.port' => (int) $this->mail_port,
            'mail.mailers.smtp.username' => $this->mail_username,
            'mail.mailers.smtp.password' => $this->mail_password,
            'mail.mailers.smtp.verify_peer' => false,
            'mail.mailers.smtp.stream' => [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ],
            'mail.from.address' => $this->mail_from_address,
            'mail.from.name' => $this->mail_from_name,
        ]);
    }
}
