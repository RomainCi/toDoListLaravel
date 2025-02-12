<?php

namespace App\Service;

use App\Models\User;
use App\Models\UserProfil;
use Illuminate\Support\Facades\Auth;

class ProfilService
{
    public function store(string $path,User $user):void
    {
        UserProfil::create([
            "user_id"=>$user->id,
            "picture"=>$path,
        ]);
    }

    public function index():string | null
    {
        $user = User::with("profil")->find(Auth::id());
        if (!$user || !$user->profil) {
            return null;
        }
        if (!$user->profil->picture) {
           return null;
        }
        return $user->profil->picture;
    }

    public function destroy(UserProfil $profil):void
    {
        $profil->delete();
    }

    public function update(string $path,User $user):void
    {
        $user->profil->picture = $path;
        $user->save();
    }
}
