<?php
use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('update-role', function () {
    Queue::fake();
    Notification::fake();
    $user = User::factory()->create();
    $invite=User::factory()->create();
    $invite2=User::factory()->create();
    $otherProjects = Project::factory(2)->create();
    $projects = Project::factory(1)->create();

    $this->actingAs($user);
    /** @var Project $otherProject */
    foreach ($otherProjects as $otherProject) {
        ProjectUser::factory()->create([
            'user_id' => $invite2->id,
            "project_id" => $otherProject->id,
            "role" => "admin",
        ]);
    }
    /** @var Project $projects */
    foreach ($projects as $project) {
        ProjectUser::factory()->create([
            'user_id' =>$user->id,
            'project_id' => $project->id,
            'role' => 'admin',  // Exemple de rôle que tu peux attribuer
        ]);
    }
    /** @var Project $projects */
    foreach ($projects as $project) {
        ProjectUser::factory()->create([
            'user_id' =>$invite->id,
            'project_id' => $project->id,
            'role' => 'visitor',  // Exemple de rôle que tu peux attribuer
        ]);
    }
    dump($invite2->id);
    $data = [
        "user_id" => $invite->id,
        "role"=>"editor"
    ];
    $response = $this->withHeaders([
        'Origin' => 'http://127.0.0.1',
    ])->putJson('/api/project/user/'.$projects[0]->id,$data);
    dump($response->json());
    $response->assertStatus(200);
    Queue::assertPushed(\App\Jobs\ChangeRoleUserJob::class);
});

test("delete-user-projet",function(){
    $user = User::factory()->create();
    $invite=User::factory()->create();
    $invite2=User::factory()->create();
    $otherProjects = Project::factory(2)->create();
    $projects = Project::factory(1)->create();
    $projectUser = null;
    $this->actingAs($user);
    /** @var Project $otherProject */
    foreach ($otherProjects as $otherProject) {
        ProjectUser::factory()->create([
            'user_id' => $invite2->id,
            "project_id" => $otherProject->id,
            "role" => "admin",
        ]);
    }
    /** @var Project $projects */
    foreach ($projects as $project) {
        ProjectUser::factory()->create([
            'user_id' =>$user->id,
            'project_id' => $project->id,
            'role' => 'admin',  // Exemple de rôle que tu peux attribuer
        ]);
    }
    /** @var Project $projects */
    foreach ($projects as $project) {
       $projectUser= ProjectUser::factory()->create([
            'user_id' =>$invite->id,
            'project_id' => $project->id,
            'role' => 'visitor',  // Exemple de rôle que tu peux attribuer
        ]);
    }
    $this->assertDatabaseCount('project_user', 4);
    $data = [
        "user_id" => $invite->id,
    ];
    $response = $this->withHeaders([
        'Origin' => 'http://127.0.0.1',
    ])->deleteJson('/api/project/user/'.$projects[0]->id,$data);
    $response->assertStatus(200);
  $this->assertDatabaseCount('project_user', 3);
    $this->assertModelMissing($projectUser);
});

test('project-view-user', function () {
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
    $projects = Project::factory(1)->create();
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
            'role' => 'visitor',  // Exemple de rôle que tu peux attribuer
        ]);
    }
    foreach ($projects as $project) {
        ProjectUser::factory()->create([
            'user_id' =>$invite2->id,
            'project_id' => $project->id,
            'role' => 'editor',  // Exemple de rôle que tu peux attribuer
        ]);
    }
    $response = $this->withHeaders([
        'Origin' => 'http://127.0.0.1',
    ])->getJson('/api/project/user/'.$projects[0]->id);
    dump($response->json());
    dump($user->profil);
    $response->assertStatus(200);
});




