<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calculation extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'session_token',
        'expression',
        'result',
        'had_error',
        'error_message',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'had_error' => 'boolean',
    ];
}
