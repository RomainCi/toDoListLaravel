<?php

namespace App\Http\Controllers\Api;

use AllowDynamicProperties;
use App\Http\Controllers\Controller;
use App\Http\Requests\Project\StoreRequest;
use App\Http\Requests\Project\UpdateRequest;
use App\Http\Resources\BaseResource;
use App\Http\Resources\ProjectCollection;
use App\Http\Resources\ProjectResource;
use App\Jobs\ChangeRoleUserJob;
use App\Models\Project;
use App\Models\User;
use App\Notifications\ChangeRoleUserNotification;
use App\Service\ErrorService;
use App\Service\ProjectService;
use App\Service\ProjectUserService;
use App\Service\S3Service;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;


#[AllowDynamicProperties] class ProjectController extends Controller
{
    protected ErrorService $errorService;
    protected S3Service $s3Service;
    protected ProjectService $projectService;

    public function __construct(ErrorService $errorService, S3Service $s3Service, ProjectService $projectService, ProjectUserService $projectUserService)
    {
        $this->errorService = $errorService;
        $this->s3Service = $s3Service;
        $this->projectService = $projectService;
        $this->projetUserService = $projectUserService;
    }

    public function store(StoreRequest $request): JsonResponse
    {
        $validateData = $request->validated();
        $image = $request->file('background_image');
        $success = false;
        $path = null;
        try {
            DB::beginTransaction();
            $user = User::find(Auth::id());
            if(isset($validateData['background_image'])){
                $path = $this->s3Service->put($image, $user, "project");
            }
            $project = $this->projectService->store($validateData, $path);
            $this->projetUserService->store($user, $project);
            DB::commit();
            $success = true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Une erreur est survenue dans le ProjectController Store" . $e->getMessage(), ['exception' => $e]);
        }
        return (new BaseResource([
            'success' => $success,
            'message' => $success ? "Votre projet a bien été créé." : $this->errorService->message()
        ]))->response()->setStatusCode($success ? 201 : 500);
    }

    /**
     * @throws Exception
     */
    public function index():JsonResponse
    {
        try {
            $projects = User::where("id", Auth::id())
                ->with(["projects.users.profil"])
                ->first()
                ->projects()
                ->paginate(5);
            $projects = $this->projectService->index($projects);
            return (ProjectCollection::collection($projects))->response()->setStatusCode(200);
        }catch (Exception $e) {
            Log::error("Une erreur est survenue dans le ProjectController Store" . $e->getMessage(), ['exception' => $e]);
            return (new BaseResource([
                'success' => false,
                'message' =>  $this->errorService->message()
            ]))->response()->setStatusCode( 500);
        }
    }

    /**
     * @throws Exception
     */
    public function update(UpdateRequest $request, Project $project)
    {
        Gate::authorize('update-project',$project);
       $validated =  $request->validated();
        $image = $request->file('background_image');
        $success = false;
        $path = null;
        $url = null;
        try{
            DB::beginTransaction();
            $user = User::find(Auth::id());
            if(isset($validated['background_image'])){
                $path = $this->s3Service->put($image, $user, "project");
                $url = $this->s3Service->getUrl($path);
            }
            if($project->background_image){
                $this->s3Service->destroy($project->background_image);
            }
            $this->projectService->update($validated,$project,$path);
            DB::commit();
            $success = true;
        }catch (Exception $e) {
            DB::rollBack();
            Log::error("Une erreur est survenue dans le ProjectController update" . $e->getMessage(), ['exception' => $e]);
        }
        $project->background_image = $url;
        return response()->json([
            'success' => $success,
            'message' => $success ? "Votre projet a bien été changé." : $this->errorService->message(),
            'project' => $success ? new ProjectResource($project) : null,
        ], $success ? 200 : 500);
    }

    public function updateRole(Request $request, Project $project)
    {
        Gate::authorize('update-project',$project);
        $validate = $request->validate([
            "role" => "required",
            "user_id" => Rule::exists('project_user', 'user_id')->where('project_id', $project->id),
        ]);
        try {
            DB::beginTransaction();
            $invite = User::with('projects')->find($validate['user_id']);
            $this->projectService->updateRole($validate,$project,$invite);
            Log::info("🚀 Dispatch du job ChangeRoleUserJob pour user_id={$invite->id}, project_id={$project->id}, role={$validate['role']}");

            ChangeRoleUserJob::dispatch($project,$invite,$validate['role']);
            DB::commit();
            return response()->json(["success" => true]);
//
        }catch (Exception $e) {
            DB::rollBack();
            Log::error("Une erreur est survenue dans le ProjectController updateRole" . $e->getMessage(), ['exception' => $e]);
        }
    }
}
