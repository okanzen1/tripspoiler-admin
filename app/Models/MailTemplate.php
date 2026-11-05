<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * E-posta yazarken eklenen hazır yanıt.
 */
class MailTemplate extends Model
{
    protected $fillable = ['title', 'body', 'sort_order'];
}
