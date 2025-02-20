<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectUser\DeleteRequest;
use App\Http\Requests\ProjectUser\UpdateRoleRequest;
use App\Http\Resources\BaseResource;
use App\Http\Resources\UsersResource;
use App\Jobs\ChangeRoleUserJob;
use App\Models\Invitation;
use App\Models\Project;
use App\Models\User;
use App\Service\ErrorService;
use App\Service\ProjectService;
use App\Service\ProjectUserService;
use App\Service\S3Service;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class ProjectUserController extends Controller
{
    protected ErrorService $errorService;
    protected S3Service $s3Service;
    protected ProjectService $projectService;
    protected ProjectUserService $projectUserService;
    public function __construct(ErrorService $errorService, S3Service $s3Service, ProjectService $projectService, ProjectUserService $projectUserService)
    {
        $this->errorService = $errorService;
        $this->s3Service = $s3Service;
        $this->projectService = $projectService;
        $this->projectUserService = $projectUserService;

    }
    public function update(UpdateRoleRequest $request, Project $project):JsonResponse
    {
        Gate::authorize('update-project',$project);
        $validate= $request->validated();
        $success=false;
        try {
            DB::beginTransaction();
            $invite = User::with('projects')->find($validate['user_id']);
            $this->projectService->updateRole($validate,$project,$invite);
            ChangeRoleUserJob::dispatch($project,$invite,$validate['role']);
            $success=true;
            DB::commit();
        }catch (Exception $e) {
            DB::rollBack();
            Log::error("Une erreur est survenue dans le ProjectUSerController update" . $e->getMessage(), ['exception' => $e]);
        }
        return (new BaseResource([
            'success' => $success,
            'message' =>$success?"Le role a bien été changé":$this->errorService->message(),
        ]))->response()->setStatusCode($success ? 200 : 500);
    }
    public function destroy(DeleteRequest $request,Project $project):JsonResponse
    {
        Gate::authorize('delete-project',$project);
        $validate = $request->validated();
        $success=false;
        try {
            DB::beginTransaction();
            $this->projectUserService->delete($validate,$project);
            $success = true;
            DB::commit();
        }catch (Exception $e){
          DB::rollBack();
          Log::error("Une erreur est survenue dans le ProjectUSerController Delete" . $e->getMessage(), ['exception' => $e]);
        }
        return (new BaseResource([
            'success' => $success,
            'message' =>$success?"L'utilisateur a bien été supprimé du projet":$this->errorService->message(),
        ]))->response()->setStatusCode($success ? 200 : 500);
    }


    /**
     * @throws Exception
     */
    public function show(Project $project): JsonResponse
    {
        Gate::authorize('view-user',$project);
        try {
            $this->projectUserService->show($project);
            return (UsersResource::collection($project->users))->response()->setStatusCode(200);
        }catch (Exception $e) {
            Log::error("Une erreur est survenue dans le ProjectUSerController Show" . $e->getMessage(), ['exception' => $e]);
            return (new BaseResource([
                'success' => false,
                'message' =>  $this->errorService->message()
            ]))->response()->setStatusCode( 500);
        }
    }
}
