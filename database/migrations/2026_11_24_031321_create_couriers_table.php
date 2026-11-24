<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Buat tabel couriers.
     */
    public function up(): void
    {
        Schema::create('couriers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->index();
            $table->string('phone', 20)->unique();
            $table->string('email')->nullable()->unique();
            $table->unsignedTinyInteger('level')->index();
            $table->date('registered_at')->index();
            $table->timestamps();
        });
    }

    /**
     * Hapus tabel couriers.
     */
    public function down(): void
    {
        Schema::dropIfExists('couriers');
    }
};
