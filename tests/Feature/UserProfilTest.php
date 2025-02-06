<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);


test('user-profil-store-not-working',function(){
    $user = User::factory()->create();

    // 2. Simule une connexion
    $this->actingAs($user);
    $file = [
      "picture" => UploadedFile::fake()->create("document.php",1*1024)];
    $response = $this->withHeaders([
        'Origin' => 'http://127.0.0.1:8000',
    ])->postJson('/api/profil', $file);
    $response->assertStatus(422);

});

test('user-profil-store-good',function(){
    Storage::fake('s3');
    $user = User::factory()->create();
    $this->actingAs($user);
    $file = [
        "picture" => UploadedFile::fake()->create("picture.png",1*50)];
      $response = $this->withHeaders([
          'Origin' => 'http://127.0.0.1:8000',
      ])->postJson('/api/profil', $file);
      $response->assertStatus(200);
      $path = $user->profil->picture;
      Storage::assertExists($path);
});

test('user-profil-index',function(){
    Storage::fake('s3');
    $user = User::factory()->create();
    $this->actingAs($user);
    // Simulate uploading a picture to S3 and associate it with the user
    $image = UploadedFile::fake()->image('profile-picture.jpg');  // Crée une image fictive
    // Enregistres cette image dans la table 'pictures' via un modèle ou directement
    $user->profil()->create([
        'picture' => $image->store('profile-pictures', 's3'),  // Utilise 's3' comme disque configuré
    ]);
      $response = $this->withHeaders([
          'Origin' => 'http://127.0.0.1:8000',
      ])->getJson('/api/profil');
      $response->assertStatus(200);
      $response->assertJson([
        "data" => [
            "url" => !null,
            "success" => true,
            "message" => "Un lien a été généré."
        ]
        ]);
});

test('user-profil-index-not-picture',function(){
    Storage::fake('s3');
    $user = User::factory()->create();
    $this->actingAs($user);
      $response = $this->withHeaders([
          'Origin' => 'http://127.0.0.1:8000',
      ])->getJson('/api/profil');
      $response->assertStatus(200);
      $response->assertJson([
        "data" => [
            "url" => null,
            "success" => true,
            "message" => "Aucune image n'a été trouvée."
        ]
        ]);
});


test('delete-profil-not-image',function(){
    Storage::fake('s3');
    $user = User::factory()->create();
    $this->actingAs($user);
    $response = $this->withHeaders([
        'Origin' => 'http://127.0.0.1:8000',
    ])->deleteJson('/api/profil');
    $response->assertStatus(400);
    $this->assertNull($user->profil);
});

test('delete-profil',function(){
    Storage::fake('s3');
    $user = User::factory()->create();
    $this->actingAs($user);
    $image = UploadedFile::fake()->image('profile-picture.jpg');  // Crée une image fictive
    $user->profil()->create([
        'picture' => $image->store('profile-pictures', 's3'),  // Utilise 's3' comme disque configuré
    ]);
    $response = $this->withHeaders([
        'Origin' => 'http://127.0.0.1:8000',
    ])->deleteJson('/api/profil');
    $response->assertStatus(200);
    $this->assertNull($user->profil);
});