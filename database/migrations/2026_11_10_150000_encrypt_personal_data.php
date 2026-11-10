<?php

use App\Support\Privacy;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kişisel veri veritabanında açık durmaz (App\Support\Privacy):
 *  - visits, affiliate_clicks: IP şifreli + ip_hash (gruplama/arama). Eski kayıtlar sıfırlanır (açık IP içeriyorlardı).
 *  - reviews: ad şifreli; email_hash anahtarlı parmak izine çevrilir.
 *  - users: ad, soyad, e-posta şifreli; giriş email_hash ile.
 *  - activity_logs: kişi adı, IP, tarayıcı, kayıt adı ve değişiklikler şifreli.
 *  - blog_subscribers: email_hash anahtarlı parmak izine çevrilir (e-posta zaten şifreli).
 *  - sessions: IP ve tarayıcı artık yazılmaz (App\Support\PrivateSessionHandler); eskiler silinir.
 *  - password_reset_tokens: temizlenir.
 * Geri alınamaz (şifreli veri tekrar açık yazılmaz).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Analitik: sıfırla, IP sütunu şifreli metin + parmak izi
        foreach (['visits', 'affiliate_clicks'] as $name) {
            DB::table($name)->truncate();
            Schema::table($name, function (Blueprint $table) {
                $table->dropIndex(['ip']);
            });
            Schema::table($name, function (Blueprint $table) {
                $table->text('ip')->nullable()->change();
                $table->char('ip_hash', 64)->nullable()->after('ip')->index();
            });
        }

        // 2) Yorumlar: ad şifreli
        Schema::table('reviews', function (Blueprint $table) {
            $table->text('name')->change();
        });
        DB::table('reviews')->orderBy('id')->each(function ($row) {
            $email = $this->plain($row->email);
            DB::table('reviews')->where('id', $row->id)->update([
                'name' => $this->encrypt($row->name),
                'email_hash' => Privacy::hash($email),
            ]);
        });

        // 3) Panel kullanıcıları: ad, soyad, e-posta şifreli; giriş email_hash ile
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->text('first_name')->change();
            $table->text('last_name')->change();
            $table->text('email')->change();
            $table->char('email_hash', 64)->nullable()->after('email')->unique();
        });
        DB::table('users')->orderBy('id')->each(function ($row) {
            DB::table('users')->where('id', $row->id)->update([
                'first_name' => $this->encrypt($row->first_name),
                'last_name' => $this->encrypt($row->last_name),
                'email' => $this->encrypt($row->email),
                'email_hash' => Privacy::hash($this->plain($row->email)),
            ]);
        });

        // 4) İşlem kayıtları
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->text('user_name')->nullable()->change();
            $table->text('ip')->nullable()->change();
            $table->text('user_agent')->nullable()->change();
            $table->text('subject_label')->nullable()->change();
            $table->longText('changes')->nullable()->change();
        });
        DB::table('activity_logs')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('activity_logs')->where('id', $row->id)->update([
                    'user_name' => $this->encrypt($row->user_name),
                    'ip' => $this->encrypt($row->ip),
                    'user_agent' => $this->encrypt($row->user_agent),
                    'subject_label' => $this->encrypt($row->subject_label),
                    'changes' => $this->encrypt($row->changes),
                ]);
            }
        });

        // 5) Bülten: parmak izi anahtarlı
        DB::table('blog_subscribers')->orderBy('id')->each(function ($row) {
            DB::table('blog_subscribers')->where('id', $row->id)->update(['email_hash' => Privacy::hash($this->plain($row->email))]);
        });

        // 6) Oturumlar ve şifre sıfırlama
        DB::table('sessions')->update(['ip_address' => null, 'user_agent' => null]);
        if (Schema::hasTable('password_reset_tokens')) {
            DB::table('password_reset_tokens')->truncate();
        }
    }

    public function down(): void
    {
        // Şifreli veri tekrar açık yazılmaz.
    }

    /** Açık değeri şifreler; zaten şifreliyse dokunmaz (migration iki kez çalışırsa) */
    private function encrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return $this->isEncrypted($value) ? $value : Crypt::encryptString($value);
    }

    /** Şifreliyse çözer, değilse olduğu gibi */
    private function plain(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value;
        }
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
};
