<?php

namespace App\Service;

use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;

class ProjectUserService
{
    public function store(User $user, Project $project):void
    {
        ProjectUser::create([
            "role" => "admin",
            "user_id" => $user->id,
            "project_id"=>$project->id
        ]);
    }
}
