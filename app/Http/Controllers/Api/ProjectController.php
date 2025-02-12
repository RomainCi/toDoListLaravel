<?php

namespace App\Http\Controllers\Api;

use AllowDynamicProperties;
use App\Http\Controllers\Controller;
use App\Http\Requests\Project\StoreRequest;
use App\Http\Resources\BaseResource;
use App\Http\Resources\ProjectCollection;
use App\Models\Project;
use App\Models\User;
use App\Service\ErrorService;
use App\Service\ProjectService;
use App\Service\ProjectUserService;
use App\Service\S3Service;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;


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

    public function update(Request $request,Project $project)
    {
        Gate::authorize('update-project',$project);

    }
}
