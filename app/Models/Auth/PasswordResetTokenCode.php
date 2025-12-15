<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Model;

class PasswordResetTokenCode extends Model
{

    protected $table = 'password_reset_token_codes';
    //
    protected $fillable = [
        'email',
        'code',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];


    public $timestamps = true;
}
