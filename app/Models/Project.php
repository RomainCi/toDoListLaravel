<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
       'title',
       "background_color",
       "background_image" 
    ];
}
