<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Project::class;
    public function definition(): array
    {
        // Choisir aléatoirement entre une image ou une couleur
        $isImageNull = $this->faker->boolean();
        $path = null;
        if (!$isImageNull) {
            // Créer une image factice avec un contenu temporaire
            $imageContent = 'Fake Image Content'; // Contenu factice de l'image
            $imageName = Str::random(10) . '.jpg'; // Nom unique pour éviter les conflits

            // Sauvegarder sur S3 (on crée un fichier temporaire)
            $tempFile = tmpfile();
            fwrite($tempFile, $imageContent);
            $imagePath = stream_get_meta_data($tempFile)['uri'];

            // Envoie le fichier vers S3 avec un nom unique
            $path = Storage::disk('s3')->putFileAs('images', new File($imagePath), $imageName);

            // Ferme le fichier temporaire
            fclose($tempFile);
        }
        return [
            "title" => $this->faker->word(),
            "background_image" => $isImageNull ? null :$path, // Si true, l'image est null
            "background_color" => !$isImageNull ? null : '#'.substr(md5(rand()), 0, 6), // Si false, la couleur est null
        ];
    }
}
