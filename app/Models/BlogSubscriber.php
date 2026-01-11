<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class BlogSubscriber extends Model
{
    protected $fillable = [
        'email',
        'status',
        'unsubscribe_token',
    ];

    protected $attributes = [
        'status' => 1,
    ];

    protected static function booted()
    {
        static::creating(function ($subscriber) {
            if (empty($subscriber->unsubscribe_token)) {
                $subscriber->unsubscribe_token = Str::uuid();
            }
        });
    }

    public function getEmailAttribute($value)
    {
        return Crypt::decryptString($value);
    }

    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = Crypt::encryptString($value);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
