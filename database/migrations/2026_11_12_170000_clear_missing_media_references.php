<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    // İlk gece temizliğinin sildiği banner'ların kayıtları: dosyası olmayan resim kayıtları boşaltılır,
    // site varsayılana geçer (telefonda masaüstü resmi vb.). Komut her gece media:prune'dan sonra da çalışır.
    public function up(): void
    {
        Artisan::call('media:clear-missing');
    }

    public function down(): void
    {
        //
    }
};
