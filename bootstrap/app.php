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
        // Gönderilen veri php.ini post_max_size'ı aşınca PHP tüm formu atar: _method (PUT) kaybolur ve 405 olur,
        // ya da Laravel 413 verir. İkisinde de anlaşılır bir mesajla geri dön.
        $exceptions->render(function (Throwable $e, Illuminate\Http\Request $request) {
            $tooLarge = $e instanceof Illuminate\Http\Exceptions\PostTooLargeException
                || ($e instanceof Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
                    && $request->isMethod('POST') && empty($_POST) && empty($_FILES)
                    && (int) $request->server('CONTENT_LENGTH') > App\Support\UploadLimit::requestBytes());
            if (!$tooLarge) {
                return null;
            }

            $message = __('Gönderilen dosyalar çok büyük (sunucu sınırı :max MB). Daha küçük resim seç ya da resimleri ayrı ayrı kaydet.', [
                'max' => round(App\Support\UploadLimit::requestBytes() / 1024 ** 2, 1),
            ]);

            return $request->expectsJson() || $request->ajax()
                ? response()->json(['error' => $message], 413)
                : back()->with('error', $message);
        });

        // Sayfa çok uzun açık kaldıysa form güvenlik anahtarı (CSRF) eskir: hata sayfası yerine geri dön, yazılanlar kaybolmasın
        $exceptions->render(function (Symfony\Component\HttpKernel\Exception\HttpException $e, Illuminate\Http\Request $request) {
            if ($e->getStatusCode() === 419 && !$request->expectsJson()) {
                return back()->withInput($request->except(['_token', 'password', 'password_confirmation']))
                    ->with('error', __('Sayfa uzun süre açık kaldığı için işlem yapılamadı. Bilgilerin korundu, tekrar kaydet.'));
            }
        });
    })->create();
