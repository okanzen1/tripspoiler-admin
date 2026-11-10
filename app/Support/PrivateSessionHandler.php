<?php

namespace App\Support;

use Illuminate\Session\DatabaseSessionHandler;

/**
 * Veritabanı oturumu, ama ziyaretçinin IP'si ve tarayıcısı yazılmaz (kişisel veri açık durmasın).
 * AppServiceProvider'da "database" sürücüsünün yerine kayıtlı; .env'de değişiklik gerekmez.
 */
class PrivateSessionHandler extends DatabaseSessionHandler
{
    protected function addRequestInformation(&$payload)
    {
        return $this;
    }
}
