<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Jobs\DeleteUnverifiedUser;
use App\Jobs\SendVerificationEmail;
use App\Models\User;
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

    public function loginIndex(): RedirectResponse
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000'); // Valeur par défaut si l'ENV est absent
        return redirect()->away($frontendUrl);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->safe()->only(['password', 'email']);
        $remember = $request->safe()->only(['remember']);
        try {
            if (Auth::attempt($credentials, $remember)) {
                $request->session()->regenerate();
                return response()->json([
                    "success" => true,
                    "message" => "Connexion réussi",
                ], 200);
            }
            return response()->json([
                'success' => false,
                "message" => "Les identifiants sont incorrects.",
            ], 401);
        } catch (Exception $e) {
            Log::error("Erreur lors de la connexion : " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                "bug" => $e->getMessage(),
                "success" => false,
                "message" => "Une erreur interne est survenue. Veuillez réessayer plus tard."
            ], 500);
        }
    }


    public function register(RegisterRequest $request, UserService $userService): JsonResponse
    {
        $validated = $request->validated();
        try {
            ///START TRANSACTION
            DB::beginTransaction();
            ///CREATE USER WITH SERVICE
            $user = $userService->create($validated);
            //AUTHENTIFICATION USER
            Auth::login($user);
            $request->session()->regenerate();
            //SEND EMAIL WITH JOB
            SendVerificationEmail::dispatch($user);
            DeleteUnverifiedUser::dispatch($user)->delay(now()->addMinutes(2));
            DB::commit();
            return response()->json([
                "success" => true,
                "message" => "Un e-mail de vérification a été envoyé."
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Erreur lors de la création : " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                "success" => false,
                "message" => "Une erreur interne est survenue. Veuillez réessayer plus tard."
            ], 500);
        }
    }

    public function logout(Request $request ,UserService $userService): JsonResponse
    {      
        $success = $userService->logout($request);
        if($success){
            return response()->json([
                "success" => $success,
                'message' => 'Déconnexion réussie',
            ],200);
        }
            return response()->json([
                "success" => $success,
                "message" => "Une erreur interne est survenue. Veuillez réessayer plus tard."
            ], 500);   
    }


    public function forgetPassword(Request $request): JsonResponse
    {
        try{
            $request->validate(['email' => 'required|email']);
            ////VERIFIER QUE PASSWORD RESET TOKEN CE DELETE/////
            Password::sendResetLink($request->only('email'));
            return response()->json([
                "success" => true,
                'message' => 'Un e-mail de mot passe oublié a été envoyé.',
            ],200);
        }catch(Exception $e){
            Log::error("Erreur lors de la forgetPassword : " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                "success" => false,
                "message" => "Une erreur interne est survenue. Veuillez réessayer plus tard."
            ], 500);
        }
       
    }

    public function sendVerificationEmail(Request $request):JsonResponse
    {
        try{
            $user = Auth::user(); // Récupère l'utilisateur authentifié

        // Vérifie si l'utilisateur a déjà vérifié son email
        if ($user->email_verified_at !== null) {
            return response()->json([
                "success" => false,
                'message' => 'L\'email est déjà vérifié.'
            ], 200);
        }

        // Envoie un e-mail de vérification via le job
        SendVerificationEmail::dispatch($user);

        return response()->json([
            "success" => true,
            "message" => 'Un e-mail de vérification a été envoyé.'
        ], 200);
        }catch(Exception $e){
            Log::error("Erreur lors de la sendVerificationEmail : " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                "success" => false,
                "message" => "Une erreur interne est survenue. Veuillez réessayer plus tard."
            ], 500);
        }
        
    }

    public function resetPassword(string $token):RedirectResponse | JsonResponse
    {   
        try{
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
        $resetUrl = $frontendUrl . '/reset-password?token=' . $token;
        return redirect()->away($resetUrl);
        }catch(Exception $e){
            Log::error("Erreur lors de resetPassword : " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                "success" => false,
                "message" => "Une erreur interne est survenue. Veuillez réessayer plus tard."
            ], 500);
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
                "message" => "Une erreur interne est survenue. Veuillez réessayer plus tard."
            ], 500);
        }
    }
}
