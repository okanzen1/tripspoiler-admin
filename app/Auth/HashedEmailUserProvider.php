<?php

namespace App\Auth;

use App\Support\Privacy;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Giriş: e-posta veritabanında şifreli, kullanıcı e-postanın parmak iziyle (email_hash) bulunur.
 * config/auth.php › providers.users.driver = "hashed-email" (AppServiceProvider'da kayıtlı).
 */
class HashedEmailUserProvider extends EloquentUserProvider
{
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (array_key_exists('email', $credentials)) {
            $credentials['email_hash'] = Privacy::hash($credentials['email']);
            unset($credentials['email']);
        }

        return parent::retrieveByCredentials($credentials);
    }
}
