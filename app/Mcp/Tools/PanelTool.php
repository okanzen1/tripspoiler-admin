<?php

namespace App\Mcp\Tools;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use RuntimeException;

/**
 * Panel araçlarının ortak tabanı: run() dizi dönerse JSON, metin dönerse metin;
 * bulunamayan kayıt / geçersiz veri Claude'a anlaşılır hata olarak gider.
 */
abstract class PanelTool extends Tool
{
    abstract protected function run(Request $request): array|string;

    public function handle(Request $request): Response
    {
        try {
            $result = $this->run($request);
        } catch (ValidationException $e) {
            return Response::error('Invalid input: ' . collect($e->errors())->flatten()->implode(' '));
        } catch (ModelNotFoundException) {
            return Response::error('Record not found.');
        } catch (RuntimeException $e) {
            return Response::error($e->getMessage());
        }

        return is_array($result) ? Response::json($result) : Response::text($result);
    }
}
