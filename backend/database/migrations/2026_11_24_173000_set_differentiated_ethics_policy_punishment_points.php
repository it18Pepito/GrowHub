<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ethics_policies')) {
            return;
        }

        $rules = [
            'Kebocoran Data Rahasia Perusahaan & Pelanggan' => ['points' => 5, 'severity' => 'high'],
            'Konten SARA, Ujaran Kebencian & Pelecehan' => ['points' => 5, 'severity' => 'high'],
            'Spamming & Manipulasi Poin Gamifikasi' => ['points' => 3, 'severity' => 'medium'],
            'Promosi Pribadi / Iklan di Luar Pekerjaan' => ['points' => 2, 'severity' => 'medium'],
            'Informasi Menyesatkan & Dokumentasi Tidak Asli' => ['points' => 1, 'severity' => 'low'],
        ];

        foreach ($rules as $title => $data) {
            DB::table('ethics_policies')
                ->where('title', 'like', "%{$title}%")
                ->update([
                    'point_deduction' => $data['points'],
                    'severity' => $data['severity'],
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ethics_policies')) {
            DB::table('ethics_policies')->update([
                'point_deduction' => 1,
                'updated_at' => now(),
            ]);
        }
    }
};
