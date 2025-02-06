<?php

namespace App\Service;

use App\Models\User;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Exceptions\FileNotFoundException;

class S3Service
{
    public function put(UploadedFile $image,User $user):string
    {
        $safeUserName = preg_replace('/[^a-zA-Z0-9-_]/', '_', $user->last_name); 
        $path = $image->storeAs('photoProfil/'. $safeUserName ,$image->hashName());
        $content = file_get_contents($image->getRealPath());
        Storage::disk('s3')->put($path,$content);
        if(!$this->exist($path)){
            throw new Exception("Le fichier n'existe pas sur S3 après l'upload.");
        } 
        return $path;
    }
    public function getUrl(string $path):string
    {  
        if(!$this->exist($path)){
            throw new Exception("Le fichier n'existe pas sur S3.");
        } 
        return Storage::temporaryUrl(
            $path, now()->addMinutes(5)
        );     
    }

    public function destroy(string $path):void
    {
        if(!$this->exist($path)){
            throw new Exception("Le fichier n'existe pas sur S3.");
        } 
        Storage::disk('s3')->delete($path);
    }

    private function exist($path):bool
    {
        return Storage::disk('s3')->exists($path);
    }
}
