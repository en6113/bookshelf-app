<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\DatabaseNotification;

class Notification extends DatabaseNotification
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];
}
