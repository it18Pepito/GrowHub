<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HelperContact extends Model
{
    protected $fillable = [
        'name',
        'role_or_title',
        'channel',
        'contact_value',
        'available_hours',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public static function defaultContacts(): array
    {
        return [
            [
                'name' => 'IT Helpdesk & System Support',
                'role_or_title' => 'Administrator Sistem GrowHub',
                'channel' => 'whatsapp',
                'contact_value' => '+62 812-8899-7700',
                'available_hours' => 'Senin - Jumat, 08:00 - 17:00 WIB',
                'description' => 'Bantuan kendala login NIP, lupa password, reset akun, dan error teknis GrowHub.',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Human Capital Care (Learning & Culture)',
                'role_or_title' => 'HC Learning & Development Lead',
                'channel' => 'email',
                'contact_value' => 'hc-care@growhub.co.id',
                'available_hours' => 'Senin - Jumat, 08:30 - 16:30 WIB',
                'description' => 'Konsultasi seputar kurasi materi, sertifikasi, poin gamifikasi, dan akun karyawan.',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Hotline IT Support Emergency',
                'role_or_title' => 'Technical Response Center',
                'channel' => 'phone',
                'contact_value' => '(021) 5566-7788',
                'available_hours' => 'Hari Kerja 08:00 - 17:00 WIB',
                'description' => 'Hotline panggilan langsung untuk kendala akses darurat infrastruktur dan server GrowHub.',
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];
    }
}
