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
        Route::resourceVerbs(['create' => 'yeni', 'edit' => 'duzenle']);

        Gate::define('admin', fn (User $user) => $user->isAdmin());

        // Claude uygulaması bağlanırken açılan izin ekranı (OAuth, routes/ai.php)
        \Laravel\Passport\Passport::authorizationView(fn (array $parameters) => view('admin.auth.mcp-authorize', $parameters));
        \Laravel\Passport\Passport::tokensExpireIn(now()->addDays(30));
        \Laravel\Passport\Passport::refreshTokensExpireIn(now()->addMonths(6));

        // Panel işlem kaydı: tüm model değişiklikleri + giriş/çıkış (Yönetim > İşlem kaydı)
        \App\Support\ActivityLogger::register();
    }
}
