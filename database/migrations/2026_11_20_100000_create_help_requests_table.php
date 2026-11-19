<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Plan your trip" yardım talepleri (sitedeki adım adım form). Kişisel veri açık durmaz:
 * ad, e-posta ve bütün cevaplar (şehir, tarih, kişi sayısı, not…) şifreli saklanır (APP_KEY, site ve panel aynı).
 * email_hash: aynı kişinin taleplerini şifreyi açmadan eşleştirmek için tek yönlü parmak izi (App\Support\Privacy).
 * Talepler 12 ay sonra kendiliğinden silinir (HelpRequest::prunable).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_requests', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->text('email');
            $table->string('email_hash', 64)->nullable()->index();
            $table->longText('answers');
            $table->string('locale', 10)->default('en');
            $table->string('status', 20)->default('new')->index();
            $table->boolean('newsletter')->default(false);
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_requests');
    }
};
