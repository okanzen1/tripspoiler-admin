<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => App\Http\Middleware\EnsureUserHasRole::class,
        ]);
        $middleware->web(append: [App\Http\Middleware\SetPanelLocale::class, App\Http\Middleware\AjaxRedirectToJson::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Sayfa çok uzun açık kaldıysa form güvenlik anahtarı (CSRF) eskir: hata sayfası yerine geri dön, yazılanlar kaybolmasın
        $exceptions->render(function (Symfony\Component\HttpKernel\Exception\HttpException $e, Illuminate\Http\Request $request) {
            if ($e->getStatusCode() === 419 && !$request->expectsJson()) {
                return back()->withInput($request->except(['_token', 'password', 'password_confirmation']))
                    ->with('error', __('Sayfa uzun süre açık kaldığı için işlem yapılamadı. Bilgilerin korundu, tekrar kaydet.'));
            }
        });
    })->create();
