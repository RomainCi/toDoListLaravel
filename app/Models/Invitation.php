<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed $token
 * @property mixed $email
 * @property mixed $project_id
 * @property mixed $status
 * @property mixed $expires_at
 * @property mixed $accept_at
 * @property mixed $inviter_id
 */
class Invitation extends Model
{
 use HasFactory;
    protected $fillable = [
        'project_id',
        "inviter_id",
        "email",
        'token',
        'status',
        'expires_at',
        'accept_at'
    ];

}
