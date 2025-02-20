<?php

namespace App\Service;

use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
use Exception;

class ProjectUserService
{
    protected S3Service $s3Service;

    public  function  __construct(S3Service $s3Service)
    {
        $this->s3Service = $s3Service;
    }
    public function store(User $user, Project $project,string $role):void
    {
        ProjectUser::create([
            "role" => $role,
            "user_id" => $user->id,
            "project_id"=>$project->id
        ]);
    }
    public function delete(Array $validate, Project $project):void
    {
        ProjectUser::where("user_id", $validate['user_id'])->where("project_id", $project->id)->delete();
    }

    /**
     * @throws Exception
     */
    public function show(Project $project):void
    {
        foreach ($project->users as $user) {
            if ($user->profil !== null && $user->profil->picture !== null) {
                $url = $this->s3Service->getUrl($user->profil->picture);
                $user->profil->picture = $url;
            }
        }
    }
}
