<?php

namespace App\Service;

use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserService
{
    protected $authService;
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    public function create(array $validated): User
    {
        // $startTime = microtime(true);
        $userId = DB::table('users')->insertGetId([
            "last_name" => $validated['lastName'],
            "first_name" => $validated['firstName'],
            "email" => $validated['email'],
            "password" => Hash::make($validated['password']),
            "created_at" => now(),
            "updated_at" => now(),
        ]);
        
        $user = User::find($userId); // Charge le modèle seulement après l’insertion
        
        // $endTime = microtime(true);
        // Log::info("Temps d'insertion: " . ($endTime - $startTime) . " secondes");
        
        return $user;
    }

    public function destroy(Request $request): bool
    {
        try{
            $user = User::destroy(Auth::id());
            $this->authService->logout($request);
            return true;
        }catch(Exception $e){
            Log::error("Erreur lors du UserService dans destroy : " . $e->getMessage(), ['exception' => $e]);
            return false;
        }
    }

    public function index(): void {}

    public function show(): void {}

    public function updateEmail(string $email,User $user):bool
    {
        try{
            $user->email = $email;
            $user->email_verified_at = Carbon::now();
            $user->save();
            Auth::setUser($user);
            return true;
        }catch(Exception $e){
            Log::error("Erreur lors du updateEmail dans userService :". $e->getMessage(),['exception' => $e]);
            return false;
        }
       
    }

    public function updatePassword(string $password,User $user):bool
    {
        try{  
            $user->password = Hash::make($password);
            $user->save();
            Auth::setUser($user);
            return true;
        }catch(Exception $e){
            Log::error("Erreur lors du updatePassword dans userService :". $e->getMessage(),['exception' => $e]);
            return false;
        }
    }


   

    public function update(array $validated): bool
    {
        try {
            $id = Auth::id();
            $user = User::find($id);
            // Vérifie si l'utilisateur existe
            if (!$user) {
                throw new Exception("User not found.");
            }
            $user->last_name = $validated['lastName'];
            $user->first_name = $validated['firstName'];    
            $user->save();
            Auth::setUser($user);
            return true;
        } catch (Exception $e) {
            Log::error("Erreur lors du UserService dans Update : " . $e->getMessage(), ['exception' => $e]);
            return false;
        }
    }
}
