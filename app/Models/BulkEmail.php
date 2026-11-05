<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Toplu e-posta geçmişi: konu, alıcı sayısı, gönderen. Adresler ve içerik saklanmaz.
 */
class BulkEmail extends Model
{
    protected $fillable = ['subject', 'recipient_count', 'sent_by'];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
