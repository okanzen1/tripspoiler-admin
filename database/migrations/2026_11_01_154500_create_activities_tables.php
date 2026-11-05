<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // name, description, meta_* çok dilli JSON ({"en": "...", "tr": "..."}); panel şimdilik "en" düzenler
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->restrictOnDelete();
            $table->foreignId('partner_id')->nullable()->constrained()->restrictOnDelete();
            $table->json('name');
            $table->string('slug')->unique();
            $table->string('type', 20)->default('product');   // product, pass, package
            $table->text('affiliate_url')->nullable();
            $table->string('partner_product_id', 100)->nullable();
            $table->string('duration', 50)->nullable();
            $table->boolean('audio_guide')->default(false);
            $table->json('description')->nullable();
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->boolean('is_popular')->default(false);
            $table->boolean('active')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['city_id', 'active', 'sort_order']);
        });

        Schema::create('activity_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->string('path');                             // media diskinde; küçük kopyası "-sm" ekli
            $table->unsignedSmallInteger('width')->nullable();   // sitede yer ayırmak için (sayfa zıplamasın)
            $table->unsignedSmallInteger('height')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0); // 0 = kapak
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_images');
        Schema::dropIfExists('activities');
    }
};
