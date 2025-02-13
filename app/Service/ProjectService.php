<?php

namespace App\Service;

use App\Models\Project;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class ProjectService
{
    protected S3Service $s3Service;

    public  function  __construct(S3Service $s3Service)
    {
        $this->s3Service = $s3Service;
    }
    public function store(array $validated,string|null $path):Project
    {
        return Project::create([
            "title" => $validated['title'],
            "background_color" =>$validated['background_color']??null,
            "background_image" =>$path ??null,
        ]);
    }

    /**
     * @throws Exception
     */
    public function index(LengthAwarePaginator $projects):LengthAwarePaginator
    {
        foreach ($projects as $project) {
            if ($project->background_image) {
                $project->background_image = $this->s3Service->getUrl($project->background_image);
            };
            $project->users->each(function ($user) {
                if ($user->profil !== null && $user->profil->picture !==null && !Str::isUrl($user->profil->picture)) {
                    $user->profil->picture = $this->s3Service->getUrl($user->profil->picture);
                }
            });
        }
        return $projects;
    }

    /**
     * @throws Exception
     */
    public function update(array $validated, Project $project,string|null $path):void
    {
        $project->update([
            "title" => $validated['title'],
            "background_color" =>$validated['background_color']??null,
            "background_image" =>$path,
        ]);
        $project->save();
    }

    public function updateRole(array $validated, Project $project,User $user):void
    {
       $projectUser =  $user->projects()->where('id',$project->id)->first();
       $projectUser->pivot->role = $validated['role'];
       $projectUser->pivot->save();
    }
}
