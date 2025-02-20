<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
/**
 * @mixin Eloquent
 */
/**
 * @property Collection|User[] $users
 */
class Project extends Model
{
    use HasFactory;

    protected $fillable = [
       'title',
       "background_color",
       "background_image"
    ];

    public function users():BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(ProjectUser::class)
            ->withPivot('role');
    }
}
