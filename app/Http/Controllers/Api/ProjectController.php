<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\StoreRequest;
use App\Http\Requests\Project\UpdateRequest;
use App\Http\Resources\BaseResource;
use App\Http\Resources\ProjectCollection;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\User;
use App\Service\ErrorService;
use App\Service\ProjectService;
use App\Service\S3Service;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;


class ProjectController extends Controller
{
    protected ErrorService $errorService;
    protected S3Service $s3Service;
    protected ProjectService $projectService;

    public function __construct(ErrorService $errorService, S3Service $s3Service, ProjectService $projectService)
    {
        $this->errorService = $errorService;
        $this->s3Service = $s3Service;
        $this->projectService = $projectService;
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
            $this->projectService->store($user, $project,"admin");
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
                ->with(["projects"])
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
    public function update(UpdateRequest $request, Project $project): JsonResponse
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

    public function destroy(Project $project):JsonResponse
    {
        Gate::authorize('delete-project',$project);
        $success = false;
        try {
            DB::beginTransaction();
            $this->projectService->delete($project);
            $success = true;
            DB::commit();
        }catch (Exception $e){
            DB::rollBack();
            Log::error("Une erreur est survenue dans le ProjectController Delete" . $e->getMessage(), ['exception' => $e]);
        }
        return (new BaseResource([
            "success" => $success,
            "message" => $success ? "Votre projet a bien été supprimé" : $this->errorService->message()
        ]))->response()->setStatusCode($success ? 200 : 500);
    }

    public function show()
    {

    }
}
