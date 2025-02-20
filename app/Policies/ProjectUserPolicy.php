<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectUserPolicy
{
    /**
     * Create a new policy instance.
     */
  public function view(User $user,Project $project): bool
  {
      return $user->projects()->where('project_id', $project->id)->exists();
  }

  public function update(User $user,Project $project): bool
  {
      return $user->projects()->where('project_id', $project->id)
          ->wherePivot('role', 'admin')->exists();
  }
}
