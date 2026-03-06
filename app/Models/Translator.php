<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Translator extends Model
{
    protected $fillable = [
        'code',
        'name',
        'active'
    ];
}
