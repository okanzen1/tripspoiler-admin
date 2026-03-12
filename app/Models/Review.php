<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{

    protected $fillable = [
        'name',
        'email',
        'source',
        'source_id',
        'rating',
        'comment',
        'approved'
    ];

    protected $casts = [

        'name' => 'encrypted',
        'email' => 'encrypted',

    ];

}