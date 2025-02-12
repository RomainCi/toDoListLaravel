<?php

namespace App\Service;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthService
{
    public function logout(Request $request):bool
    {
        try{
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return true;
        }catch(Exception $e){
            Log::error("Erreur lors du UserService dans logoout : " . $e->getMessage(), ['exception' => $e]);
            return false;
        }
    }

    public function login($request):array
    {
        $credentials = $request->safe()->only(['password', 'email']);
        $remember = $request->safe()->only(['remember']);
        try {
            if (Auth::attempt($credentials, $remember)) {
                $request->session()->regenerate();
                return [
                    "message" => "Connexion réussie.",
                    "success" => true
                ];
            //     return (new BaseResource([
            //         'success' => true,
            //         'message' => "Connexion réussie."
            //     ]))->response()->setStatusCode(200);  
             }
             return [
                "message" => "Les identifiants sont incorrects.",
                "success" => true
             ];
        } catch (Exception $e) {
            Log::error("Erreur lors de la connexion : " . $e->getMessage(), ['exception' => $e]);
            return [
                "message" => "error",
                "success" => false
            ];
        }
    }
}
