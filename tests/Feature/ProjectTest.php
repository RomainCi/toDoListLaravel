<?php

use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('project-store-color', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $data = [
        "title" => "Project Title",
        "background_color" => "#FFF",
    ];
    $response = $this->withHeaders([
     'Origin' => 'http://127.0.0.1:8000',
 ])->postJson('/api/project', $data);
    $this->assertDatabaseCount('project_user', 1);
    $this->assertDatabaseCount('projects', 1);
    $response->assertStatus(201);
});

test('project-store-image', function () {
    Storage::fake('s3');
    $user = User::factory()->create();
    $this->actingAs($user);
    $file = UploadedFile::fake()->create("document.png",300);
    $data = [
        "title" => "Project Title",
        "background_image" => $file,
    ];
    $response = $this->withHeaders([
        'Origin' => 'http://127.0.0.1:8000',
    ])->postJson('/api/project', $data);
    $response->assertStatus(201);
    Storage::assertExists($user->projects->first()->background_image);
});

test('project-store-error', function () {
   $user= User::factory()->create();
   $this->actingAs($user);
   $file = UploadedFile::fake()->create("document.png",300);
   $data = [
       "title" => "Project Title",
       "background_image" => $file,
       "background_color" => '#FFF',
   ];
    $response = $this->withHeaders([
        'Origin' => 'http://127.0.0.1:8000',
    ])->postJson('/api/project', $data);
    $response->assertStatus(422);
});

test('project-store-null', function () {
    $user= User::factory()->create();
    $this->actingAs($user);
    $data = [
        "title" => "Project Title",
        "background_color" => null,
        'background_image' => null,
    ];
    $response = $this->withHeaders([
        'Origin' => 'http://127.0.0.1:8000',
    ])->postJson('/api/project', $data);
    $response->assertStatus(422);
});

test('project-index-validate', function () {
    Storage::fake('s3');
    $user = User::factory()->create();
    $invite=User::factory()->create();
    $invite2=User::factory()->create();
    \App\Models\UserProfil::factory()->create([
        "user_id" => $user->id,
    ]);
    \App\Models\UserProfil::factory()->create([
        "user_id" => $invite2->id,
    ]);
    $this->actingAs($user);

// Crée un projet et associe l'utilisateur à ce projet avec un rôle dans la table pivot
    /** @var Project $projects */
    $projects = Project::factory(10)->create();
    foreach ($projects as $project) {
        ProjectUser::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'role' => 'admin',  // Exemple de rôle que tu peux attribuer
        ]);
    }
    foreach ($projects as $project) {
        ProjectUser::factory()->create([
            'user_id' =>$invite->id,
            'project_id' => $project->id,
            'role' => 'visiteur',  // Exemple de rôle que tu peux attribuer
        ]);
    }
    foreach ($projects as $project) {
        ProjectUser::factory()->create([
            'user_id' =>$invite2->id,
            'project_id' => $project->id,
            'role' => 'edited',  // Exemple de rôle que tu peux attribuer
        ]);
    }
   $response = $this->withHeaders([
       'Origin' => 'http://127.0.0.1',
   ])->getJson('/api/project');
    dump($response->json());
   $response->assertStatus(200);
});

test('update', function () {
    $user = User::factory()->create();
    $test = User::factory()->create();
    /** @var Project $projects */
    $projects = Project::factory(1)->create();
    $this->actingAs($user);
    foreach ($projects as $project) {
        ProjectUser::factory()->create([
            'user_id' =>$user->id,
            'project_id' => $project->id,
            'role' => 'admin',  // Exemple de rôle que tu peux attribuer
        ]);
    }
    $response = $this->withHeaders([
        'Origin' => 'http://127.0.0.1',
    ])->patchJson('/api/project/'.$projects[0]->id);
    dump($response->json());
    $response->assertStatus(200);
});
