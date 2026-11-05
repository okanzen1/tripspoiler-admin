<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ziyaretçi yorumları: front onaysız kaydeder, panelden onaylanınca sitede görünür.
        // activity_id boş = sitenin geneli hakkında (anasayfa / yorumlar sayfası)
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->text('email')->nullable();                  // şifreli
            $table->string('email_hash', 64)->nullable()->index(); // aynı kişinin tekrarını engellemek için
            $table->unsignedTinyInteger('rating');
            $table->text('comment');
            $table->boolean('approved')->default(false)->index();
            $table->timestamps();
            $table->index(['activity_id', 'approved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
