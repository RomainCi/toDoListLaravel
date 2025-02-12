<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserProfil;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserProfilFactory extends Factory
{
    protected $model = UserProfil::class;

    public function definition(): array
    {

            // Créer une image factice avec un contenu temporaire
            $imageContent = 'Fake Image Content'; // Contenu factice de l'image
            $imageName = Str::random(10) . '.jpg'; // Nom unique pour éviter les conflits

            // Utiliser UploadedFile::fake() pour créer une image factice
            $fakeImage = UploadedFile::fake()->createWithContent($imageName, $imageContent);

            // Envoie le fichier vers S3 avec un nom unique
            $path = Storage::disk('s3')->putFileAs('profil', $fakeImage, $imageName);

        return [
            'user_id' => User::factory(),
            'picture' => $path,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
