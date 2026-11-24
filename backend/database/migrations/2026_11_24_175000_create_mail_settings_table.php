<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_settings', function (Blueprint $table) {
            $table->id();
            $table->string('mail_mailer', 50)->default('smtp');
            $table->string('mail_scheme', 50)->nullable()->default('smtps');
            $table->string('mail_host', 190)->default('smtp-1.rcoid.com');
            $table->unsignedInteger('mail_port')->default(465);
            $table->string('mail_username', 190)->nullable();
            $table->string('mail_password', 190)->nullable();
            $table->string('mail_from_address', 190)->default('noreply.pepito@rcoid.com');
            $table->string('mail_from_name', 190)->default('GrowHub - Ramayana & Co');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        DB::table('mail_settings')->insert([
            'mail_mailer' => env('MAIL_MAILER', 'smtp'),
            'mail_scheme' => env('MAIL_SCHEME', 'smtps'),
            'mail_host' => env('MAIL_HOST', 'smtp-1.rcoid.com'),
            'mail_port' => (int) env('MAIL_PORT', 465),
            'mail_username' => env('MAIL_USERNAME', 'noreply.pepito@rcoid.com'),
            'mail_password' => env('MAIL_PASSWORD', 'zespuha5'),
            'mail_from_address' => env('MAIL_FROM_ADDRESS', 'noreply.pepito@rcoid.com'),
            'mail_from_name' => env('MAIL_FROM_NAME', 'GrowHub - Ramayana & Co'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_settings');
    }
};
