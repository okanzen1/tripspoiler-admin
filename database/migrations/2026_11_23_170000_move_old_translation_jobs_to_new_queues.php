<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Yeni sıra düzeninden önce "default" sırasına giren çeviri işleri doğru sıraya taşınır: toplu çeviri işleri
 * (TranslateModel) "bulk"a, panelden tek kayıt çevirileri (TranslateLocale / FinishTranslation) öncelikli "high"a.
 * Yoksa işçi önce eski binlerce işi bitiriyor, toplu sıra (zildeki sayı) hiç ilerlemiyor görünüyordu.
 * Sıra numarası (id) değişmez: eskiler yine önce işlenir.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('jobs')->where('queue', 'default')->where('payload', 'like', '%TranslateModel%')->update(['queue' => 'bulk']);
        DB::table('jobs')->where('queue', 'default')
            ->where(fn ($q) => $q->where('payload', 'like', '%TranslateLocale%')->orWhere('payload', 'like', '%FinishTranslation%'))
            ->update(['queue' => 'high']);
    }

    public function down(): void
    {
        // geri alınmaz: işler eski sırada da aynı şekilde çalışır
    }
};
