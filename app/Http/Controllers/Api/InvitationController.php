<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invitation\StoreRequest;
use App\Http\Resources\BaseResource;
use App\Models\Project;
use App\Models\User;
use App\Notifications\InvitationNotification;
use App\Service\ErrorService;
use App\Service\InvitationService;
use App\Service\ProjectUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Http\Request;
use Exception;

class InvitationController extends Controller
{
    protected InvitationService $invitationService;
    protected ErrorService $errorService;
    protected ProjectUserService $projectUserService;
    public function __construct(InvitationService $invitationService, ErrorService $errorService,ProjectUserService $projectUserService)
    {
        $this->invitationService = $invitationService;
        $this->errorService = $errorService;
        $this->projectUserService = $projectUserService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     *
     */

    public function store(StoreRequest $request,Project $project)
    {
        ////FAIRE L'EXPIRATION ////
        Gate::authorize('update-role',$project);
        $validate = $request->validated();
        if($this->invitationService->checkInvitation($project->id,$validate['email'])){
            return (new BaseResource([
                'success' => true,
                'message' => "Une invitation a déja été envoyé"
            ]))->response()->setStatusCode(409);
        }
        $invite =User::find(auth()->id());
        $success = true;
        try{
            DB::beginTransaction();
            $user = User::with('projects')->where('email',$validate['email'])->first();
            if($user){
                if($user->projects->contains($project->id)){
                    return (new BaseResource([
                        'success' => true,
                        'message' => "L'utilisateur est déja dans le projet"
                    ]))->response()->setStatusCode(409);
                }
                $invitation =  $this->invitationService->store($project->id,$validate["email"]);
                $user->notify((new InvitationNotification($invitation->token,$user,$project,$invite))->delay(now()->addSeconds(10)));
            }else{
                $invitation =  $this->invitationService->store($project->id,$validate["email"]);
                Notification::route('mail', $validate['email'])
                    ->notify((new InvitationNotification($invitation->token,$user,$project,$invite))->delay(now()->addSeconds(10)));
            }
            DB::commit();
        }catch (Exception $e){
            DB::rollBack();
            $success = false;
            Log::error("Erreur dans InvitationStore: " . $e->getMessage(), ['exception' => $e]);
        }
        return (new BaseResource([
            'success' => $success,
            'message' => $success ? "Une invitation a été envoyé." : $this->errorService->message()
        ]))->response()->setStatusCode($success ? 200 : 500);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


    public function accept(string $token):RedirectResponse
    {
        $frontendUrl = config('app.frontend_url');
        try {
            $invitation= $this->invitationService->showWithToken($token);
            if(!$invitation || $invitation->accept_at || $invitation->expires_at < now()){
                return redirect()->away($frontendUrl.'/expiration');
            }
            $user = User::where('email', $invitation->email)->first();
            if(!$user) {
                return redirect()->away($frontendUrl.'/register?token='.$token);
            }
            // Accepter l'invitation
            $this->invitationService->update($token,'accepted');
            $project = Project::find($invitation->project_id);
            $this->projectUserService->store($user,$project,"visitor");
            return redirect()->away($frontendUrl.'/project');
        }catch (Exception $e){
            Log::error("Erreur dans InvitationControllerAccept: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->away($frontendUrl.'/expiration');
        }


    }
}
