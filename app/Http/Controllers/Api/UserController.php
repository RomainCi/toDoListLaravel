<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdatePasswordRequest;
use App\Http\Requests\User\UpdateRequest;
use App\Http\Resources\BaseResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\EmailChangeNotification;
use App\Service\ErrorService;
use App\Service\UserService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;


class UserController extends Controller
{
    protected $errorService;
    protected $userService;

    public function __construct(ErrorService $errorService,UserService $userService)
    {     
        $this->errorService = $errorService;
        $this->userService = $userService;
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(): JsonResponse
    {
        try {
            return (new UserResource(Auth::user()))
                ->additional(["success" => true])
                ->response();
        } catch (Exception $e) {
            Log::error("Erreur lors show User : " . $e->getMessage(), ['exception' => $e]);
            return (new BaseResource([
                'success' => false,
                'message' => $this->errorService->message()
            ]))->response()->setStatusCode(500);
        }
    }

    public function showForAdmin(string $id) {}

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $success = $this->userService->update($validated);
        return $success
            ? (new UserResource(Auth::user()))->additional(["success" => true])->response()
            : response()->json([
                "success" => false,
                "message" => $this->errorService->message()
            ], 500);
    }

    public function updateEmail(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email|unique:users',
            ]);
            $user = User::find(Auth::id());
            $delay = now()->addSeconds(10);
            Notification::route('mail', $request->email)
                ->notify((new EmailChangeNotification($user))->delay($delay));
            $success = true;
            $message = "Un e-mail de vérification a été envoyé.";
        } catch (Exception $e) {
            Log::error("Une erreur est survenue dans Usercontroller UpdateEmail" . $e->getMessage(), ['exception' => $e]);
            $success = false;
            $message = $this->errorService->message();
        }
        return (new BaseResource([
            'success' => $success,
            'message' => $message,
        ]))->response()->setStatusCode($success ? 200 : 500);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $password = $request->validated();
        $password = $password['password'];
        $user = User::find(Auth::id());
        $success = $this->userService->updatePassword($password, $user);
        return (new BaseResource([
            'success' => $success,
            'message' => $success ? "Le mot de passe a était mise a jour" : $this->errorService->message()
        ]))->response()->setStatusCode($success ? 200 : 500);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request): JsonResponse
    {
        $success = $this->userService->destroy($request);
        return (new BaseResource([
            'success' => $success,
            'message' => $success ? "Suppression réussie" : $this->errorService->message()
        ]))->response()->setStatusCode($success ? 200 : 500);
    }

    public function verifyEmailChange(User $user, string $email)
    {
        $success = $this->userService->updateEmail($email, $user);
        return (new BaseResource([
            'success' => $success,
            'message' => $success ? "L'email a été mis à jour" : $this->errorService->message()
        ]))->response()->setStatusCode($success ? 200 : 500);
    }
}
