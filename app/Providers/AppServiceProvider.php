<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Mailbox\ImapMailbox;
use App\Services\Mailbox\Mailbox;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // İstek başına tek IMAP bağlantısı
        $this->app->scoped(Mailbox::class, ImapMailbox::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Gizlilik: oturumlara IP / tarayıcı yazılmaz; giriş şifreli e-postanın parmak iziyle (App\Support\Privacy)
        \Illuminate\Support\Facades\Session::extend('database', fn ($app) => new \App\Support\PrivateSessionHandler(
            $app['db']->connection($app['config']['session.connection']), $app['config']['session.table'], $app['config']['session.lifetime'], $app,
        ));
        \Illuminate\Support\Facades\Auth::provider('hashed-email', fn ($app, array $config) => new \App\Auth\HashedEmailUserProvider($app['hash'], $config['model']));

        Route::resourceVerbs(['create' => 'yeni', 'edit' => 'duzenle']);

        Gate::define('admin', fn (User $user) => $user->isAdmin());

        // "Çevirisi unutulanlar" sayaçları: içerik, çeviri ya da dil değişince yeniden hesaplanır (App\Support\TranslationTodo)
        foreach ([...array_values(\App\Services\Translation\ModelTranslator::TYPES), \App\Models\TranslationState::class, \App\Models\Language::class] as $class) {
            $class::saved(fn () => \App\Support\TranslationTodo::forget());
            $class::deleted(fn () => \App\Support\TranslationTodo::forget());
        }

        // Claude bağlantısının (OAuth) şifreleme anahtarları git'e gönderilmez: sunucuda yoksa ilk istekte bir kez oluşturulur
        // (yoksa /mcp ve /oauth/authorize "500 Server Error" verir). Zamanlayıcı da aynısını yapar (routes/console.php).
        if (!file_exists(storage_path('oauth-private.key')) || !file_exists(storage_path('oauth-public.key'))) {
            try {
                \Illuminate\Support\Facades\Cache::lock('passport-keys', 30)->get(function () {
                    // php artisan passport:keys ile aynısı (o komut web isteğinde yok)
                    if (!file_exists(storage_path('oauth-private.key'))) {
                        $key = \phpseclib4\Crypt\RSA::createKey(4096);
                        file_put_contents(storage_path('oauth-public.key'), (string) $key->getPublicKey());
                        file_put_contents(storage_path('oauth-private.key'), (string) $key);
                        @chmod(storage_path('oauth-public.key'), 0660);
                        @chmod(storage_path('oauth-private.key'), 0600);
                    }
                });
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // Claude uygulaması bağlanırken açılan izin ekranı (OAuth, routes/ai.php)
        \Laravel\Passport\Passport::authorizationView(fn (array $parameters) => view('admin.auth.mcp-authorize', $parameters));
        \Laravel\Passport\Passport::tokensExpireIn(now()->addDays(30));
        \Laravel\Passport\Passport::refreshTokensExpireIn(now()->addMonths(6));

        // Panel işlem kaydı: tüm model değişiklikleri + giriş/çıkış (Yönetim > İşlem kaydı)
        \App\Support\ActivityLogger::register();
    }
}
