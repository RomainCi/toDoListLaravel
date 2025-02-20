<?php

namespace App\Service;

use App\Models\Invitation;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class InvitationService
{
    public function store(int $projectId,string $email):Invitation|false
    {
        $token = Str::random(60);
            return Invitation::create([
                "project_id" => $projectId,
                "inviter_id" => auth()->id(),
                "status" => "pending",
                "email" => $email,
                "token" =>  $token,
                "expires_at" => now()->addDays(7),
            ]);
    }

    public function checkInvitation(int $projectId,string $email): bool
    {
        return Invitation::where(["project_id" => $projectId,"email" => $email,"status"=>"pending"])->exists();
    }

    public function update(string $encryptToken,string $status):void
    {
        $token = $this->decrypt($encryptToken);
        $invitation = Invitation::where("token", $token)
            ->where("status", "pending")
            ->where("expires_at", ">", now()) // Vérifie que la date d'expiration est dans le futur
            ->first();
        $invitation->status = $status;
        $invitation->accept_at=now();
        $invitation->save();
    }
    public function showWithToken(string $encryptToken):Invitation|null
    {
       $token = $this->decrypt($encryptToken);
        return Invitation::where("token", $token)
            ->where("status", "pending")
            ->where("expires_at", ">", now()) // Vérifie que la date d'expiration est dans le futur
            ->first();
    }
    private function decrypt(string $token):string
    {
        try {
            return Crypt::decryptString($token);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            throw new \Illuminate\Contracts\Encryption\DecryptException($e->getMessage());
        }

    }
}
