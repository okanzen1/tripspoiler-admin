<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('venues', function (Blueprint $table) {
            $table->id();

            $table->json('name');
            $table->json('slug');

            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->foreignId('museum_id')->nullable()->constrained()->nullOnDelete();

            $table->foreignId('affiliate_id')->nullable()
                ->constrained('affiliate_partners')
                ->nullOnDelete();

            $table->string('affiliate_link')->nullable();

            $table->boolean('status')->default(false);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venues');
    }
};
