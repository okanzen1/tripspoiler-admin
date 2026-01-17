<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('venues');
    }

    public function down(): void
    {
        Schema::create('venues', function ($table) {
            $table->id();
            $table->timestamps();

            // ⚠️ Eski kolonlar varsa BURAYA EKLEMELİSİN
            // $table->string('title');
            // $table->text('description')->nullable();
        });
    }
};

