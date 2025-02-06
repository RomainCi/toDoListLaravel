<?php

namespace App\Service;

use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class UserService
{
    public function create(array $validated): User
    {
        $user = User::create([
            "last_name" => $validated['lastName'],
            "first_name" => $validated['firstName'],
            "email" => $validated['email'],
            "password" => Hash::make($validated['password']),
        ]);
        return $user;
    }

    public function destroy(Request $request): bool
    {
        try{
            $user = User::destroy(Auth::id());
            $this->logout($request);
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
            return false;
            Log::error("Erreur lors du updateEmail dans userService :". $e->getMessage(),['exception' => $e]);
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
            return false;
            Log::error("Erreur lors du updatePassword dans userService :". $e->getMessage(),['exception' => $e]);
        }
    }


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
