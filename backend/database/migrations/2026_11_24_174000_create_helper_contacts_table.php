<?php

use App\Models\HelperContact;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helper_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('role_or_title', 120)->nullable();
            $table->string('channel', 30)->default('whatsapp'); // whatsapp | email | phone | telegram | other
            $table->string('contact_value', 160);
            $table->string('available_hours', 120)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        $rows = array_map(
            fn (array $item) => array_merge($item, ['created_at' => $now, 'updated_at' => $now]),
            HelperContact::defaultContacts()
        );

        DB::table('helper_contacts')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('helper_contacts');
    }
};
