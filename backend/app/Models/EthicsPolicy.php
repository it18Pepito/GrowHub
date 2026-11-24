<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EthicsPolicy extends Model
{
    protected $fillable = [
        'title',
        'description',
        'point_deduction',
        'severity',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'point_deduction' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Default list of communication ethics & violation rules for GrowHub.
     */
    public static function defaultPolicies(): array
    {
        return [
            [
                'title' => 'Kebocoran Data Rahasia Perusahaan & Pelanggan',
                'description' => 'Dilarang mengunggah nomor kartu pembayaran, PIN kasir, data pribadi konsumen, atau laporan keuangan internal yang bersifat rahasia.',
                'point_deduction' => 5,
                'severity' => 'high',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'Konten SARA, Ujaran Kebencian & Pelecehan',
                'description' => 'Dilarang menggunakan kata-kata kasar, merendahkan rekan kerja atau cabang lain, serta menyebarkan konten bermuatan SARA.',
                'point_deduction' => 5,
                'severity' => 'high',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'title' => 'Spamming & Manipulasi Poin Gamifikasi',
                'description' => 'Dilarang mengunggah konten berulang tanpa makna, komentar spam, atau aktivitas fiktif semata-mata untuk mengejar poin.',
                'point_deduction' => 3,
                'severity' => 'medium',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'title' => 'Promosi Pribadi / Iklan di Luar Pekerjaan',
                'description' => 'Dilarang memposting iklan jualan pribadi, promosi pihak ketiga, atau tautan yang tidak berkaitan dengan operasional perusahaan.',
                'point_deduction' => 2,
                'severity' => 'medium',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'title' => 'Informasi Menyesatkan & Dokumentasi Tidak Asli',
                'description' => 'Dilarang menyebarkan SOP palsu, informasi hoaks internal, atau foto/video tidak sah yang tidak sesuai dengan fakta di lapangan.',
                'point_deduction' => 1,
                'severity' => 'low',
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];
    }
}

