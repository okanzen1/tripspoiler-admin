<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_contents', function (Blueprint $table) {

            // çok dilli başlık
            $table->json('title')->nullable()->after('blog_id');

            // sıralama
            $table->unsignedInteger('sort_order')->default(0)->after('content');

            // durum
            $table->boolean('status')->default(true)->after('sort_order');

            // index (opsiyonel ama önerilir)
            $table->index(['status', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('blog_contents', function (Blueprint $table) {

            $table->dropIndex(['status', 'sort_order']);

            $table->dropColumn([
                'title',
                'sort_order',
                'status',
            ]);
        });
    }
};
