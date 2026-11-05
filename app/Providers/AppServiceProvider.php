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
    }
}
