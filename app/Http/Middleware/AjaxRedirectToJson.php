<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AjaxRedirectToJson
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Sadece AJAX isteklerinde redirect yanıtlarını JSON'a çevir
        if ($request->ajax() && $response instanceof \Illuminate\Http\RedirectResponse) {
            $session = $request->session();
            $success = $session->get('success');
            $error = $session->get('error');
            $message = $success ?: ($error ?: __('İşlem tamamlandı.'));
            $type = $success ? 'success' : ($error ? 'error' : 'success');
            // Mesaj sağ üstte gösterildi; sayfa yenilenmediği için bir sonraki sayfada tekrar banner olmasın
            $session->forget(['success', 'error']);

            return response()->json([
                $type => $message,
                'redirect' => $response->getTargetUrl(),
            ]);
        }

        return $response;
    }
}