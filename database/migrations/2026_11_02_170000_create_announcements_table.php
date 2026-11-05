<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sitenin en üstündeki kayan duyuru şeridi; text çok dilli JSON, panel "en" düzenler
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->json('text');
            $table->string('link', 500)->nullable();
            $table->boolean('active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
