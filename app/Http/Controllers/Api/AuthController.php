<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Http\Resources\BaseResource;
use App\Jobs\DeleteUnverifiedUser;
use App\Jobs\SendVerificationEmail;
use App\Models\User;
use App\Service\AuthService;
use App\Service\ErrorService;
use App\Service\UserService;
use Exception;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    protected ErrorService $errorService;
    public function __construct(ErrorService $errorService)
    {
        $this->errorService = $errorService;
    }

    public function loginIndex(): RedirectResponse
    {
        $frontendUrl = config('app.frontend_url');
        return redirect()->away($frontendUrl);
    }

    public function login(LoginRequest $request,AuthService $authService): JsonResponse
    {
       $response = $authService->login($request);
        return (new BaseResource([
            'success' => $response["success"],
            'message' => $response["success"] ? $response["message"] : $this->errorService->message()
        ]))->response()->setStatusCode($response["success"] ? 200 : 500);
    }


    public function register(RegisterRequest $request, UserService $userService): JsonResponse
    {
        $validated = $request->validated();
        try {
            DB::beginTransaction();
            $user = $userService->create($validated);
            Auth::login($user);
            $request->session()->regenerate();
            SendVerificationEmail::dispatch($user);
            DeleteUnverifiedUser::dispatch($user)->delay(now()->addMinutes(20));
            DB::commit();
            $success = true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Erreur lors de la création : " . $e->getMessage(), ['exception' => $e]);
            $success = false;
        }
        return (new BaseResource([
            'success' => $success,
            'message' => $success ? "Un e-mail de vérification a été envoyé." : $this->errorService->message()
        ]))->response()->setStatusCode($success ? 201 : 500);
    }

    public function logout(Request $request ,AuthService $authService): JsonResponse
    {
        $success = $authService->logout($request);
        return (new BaseResource([
            'success' => $success,
            'message' => $success ? "Déconnexion réussie" : $this->errorService->message()
        ]))->response()->setStatusCode($success ? 200 : 500);
    }


    public function forgetPassword(Request $request): JsonResponse
    {
        try{
            $request->validate(['email' => 'required|email']);
            ////VERIFIER QUE PASSWORD RESET TOKEN CE DELETE/////
            Password::sendResetLink($request->only('email'));
            $success = true;
        }catch(Exception $e){
            Log::error("Erreur lors de la forgetPassword : " . $e->getMessage(), ['exception' => $e]);
            $success = false;
        }
        return (new BaseResource([
            'success' => $success,
            'message' => $success ? "Un e-mail de mot passe oublié a été envoyé." : $this->errorService->message()
        ]))->response()->setStatusCode($success ? 200 : 500);

    }

    public function sendVerificationEmail():JsonResponse
    {
        try{
            $user = Auth::user();
        if ($user->email_verified_at !== null) {
            return (new BaseResource([
                'success' => true,
                'message' => "L'email est déja vérifier"
            ]))->response()->setStatusCode(409);
        }
        SendVerificationEmail::dispatch($user);
        $success = true;
        }catch(Exception $e){
            Log::error("Erreur lors de la sendVerificationEmail : " . $e->getMessage(), ['exception' => $e]);
            $success = false;
        }
        return (new BaseResource([
            'success' => $success,
            'message' => $success ? "Un e-mail de vérification a été envoyé." : $this->errorService->message()
        ]))->response()->setStatusCode($success ? 200 : 500);
    }

    public function resetPassword(string $token):RedirectResponse | JsonResponse
    {
        try{
        $frontendUrl = config('app.frontendUrl');
        $resetUrl = $frontendUrl . '/reset-password?token=' . $token;
        return redirect()->away($resetUrl);
        }catch(Exception $e){
            Log::error("Erreur lors de resetPassword : " . $e->getMessage(), ['exception' => $e]);
            return (new BaseResource([
                'success' => false,
                'message' => $this->errorService->message()
            ]))->response()->setStatusCode(500);
        }
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        try{
            $validated = $request->validated();
            $status = Password::reset(
                array_intersect_key($validated, array_flip(['email', 'password', 'password_confirmation', 'token'])),
                function (User $user, string $password) {
                    $user->forceFill([
                        'password' => Hash::make($password)
                    ])->setRememberToken(Str::random(60));
                    $user->save();
                    event(new PasswordReset($user));
                }
            );
            return response()->json([
                'success' => true,
                "message" => __($status)
            ]);
        }catch(Exception $e){
            Log::error("Erreur lors de updatePassword : " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                "success" => false,
                "message" => $this->errorService->message()
            ], 500);
        }
    }

}
