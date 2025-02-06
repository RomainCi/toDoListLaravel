<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfil extends Model
{
    protected $fillable = [
        "picture",
        "user_id"
    ];
}
