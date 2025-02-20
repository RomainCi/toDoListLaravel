<?php

use App\Models\Invitation;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;

uses(RefreshDatabase::class);

test('invite-user-exist',function (){
    Notification::fake();
    $user = User::factory()->create();
    $invite=User::factory()->create();
    $this->actingAs($user);
    $projects = Project::factory(1)->create();
    foreach ($projects as $project) {
        ProjectUser::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'role' => 'admin',
        ]);
    }
//    foreach ($projects as $project) {
//        ProjectUser::factory()->create([
//            'user_id' => $invite->id,
//            'project_id' => $project->id,
//            'role' => 'visitor',
//        ]);
//    }
    $data = [
      "email" => $invite->email,
    ];
    $response = $this->withHeaders([
        'Origin' => 'http://127.0.0.1',
    ])->postJson('/api/invitation/'.$projects[0]->id,$data);
    Notification::assertSentTo(
        [$invite], \App\Notifications\InvitationNotification::class
    );
    $response->assertStatus(200);
    $this->assertDatabaseHas('invitations',[
        'email' => $invite->email,
        'project_id' => $projects[0]->id,
    ]);

});

test('invite-user-not-exist',function (){
   Notification::fake();
   $user = User::factory()->create();
   $this->actingAs($user);
    $email = 'toto@gmail.com';
   $projects = Project::factory(1)->create();
   foreach ($projects as $project) {
       ProjectUser::factory()->create([
           'user_id' => $user->id,
           'project_id' => $project->id,
           'role' => 'admin',
       ]);
   }
   $data = [
       "email" => "toto@gmail.com",
   ];
    $response = $this->withHeaders([
        'Origin' => 'http://127.0.0.1',
    ])->postJson('/api/invitation/'.$projects[0]->id,$data);
    dump($response->json());
    dump(Invitation::find(1));
    $this->assertDatabaseHas('invitations',[
        'email' => $email,
        'project_id' => $projects[0]->id,
    ]);
    Notification::assertCount(1);
});

test('invitation-accepted-not-user',function (){
    $user = User::factory()->create();
    $projects = Project::factory(1)->create();
    foreach ($projects as $project) {
        ProjectUser::factory()->create([
            "user_id" => $user->id,
            "project_id" => $project->id,
            'role' => 'admin',
        ]);
    }
   $token =  Str::random(60);
    $invitation = Invitation::create([
        "project_id" => $projects[0]->id,
        "inviter_id" => $user->id,
        "email" => "toto@gmail.com",
        "token" => $token,
        "status" => "pending",
        "expires_at"=>now()->addDay(7),
    ]);
   $encrypt = Crypt::encryptString($token);
    $response = $this->withHeaders([
        'Origin' => 'http://127.0.0.1',
    ])->getJson('/api/invitation/accept/'.$encrypt,);
    $response->assertRedirect(config('app.frontend_url').'/register?token=' . $encrypt);

});

test('invitation-accepte-user-good',function (){
    $user = User::factory()->create();
    $invite = User::factory()->create();
    $projects = Project::factory(1)->create();
    foreach ($projects as $project) {
        ProjectUser::factory()->create([
            "user_id" => $user->id,
            "project_id" => $project->id,
            'role' => 'admin',
        ]);
    }
    $token =  Str::random(60);
    Invitation::create([
        "project_id" => $projects[0]->id,
        "inviter_id" => $user->id,
        "email" => $invite->email,
        "token" => $token,
        "status" => "pending",
        "expires_at"=>now()->addDay(7),
    ]);
    $encrypt = Crypt::encryptString($token);
    $response = $this->withHeaders([
        'Origin' => 'http://127.0.0.1',
    ])->getJson('/api/invitation/accept/'.$encrypt);
    $this->assertDatabaseHas('project_user',[
        'user_id' => $invite->id,
        'project_id' => $projects[0]->id,
        'role' => 'visitor',
    ]);
    $response->assertRedirect(config('app.frontend_url').'/project');
});

test('invitation-accepted-accept-at-not-null',function (){
   $user = User::factory()->create();
   $projects = Project::factory(1)->create();
   $invite = User::factory()->create();
   foreach ($projects as $project) {
       ProjectUser::factory()->create([
           "user_id" => $user->id,
           "project_id" => $project->id,
           'role' => 'admin',
       ]);
   }
    $token =  Str::random(60);
    Invitation::create([
        "project_id" => $projects[0]->id,
        "inviter_id" => $user->id,
        "email" => $invite->email,
        "token" => $token,
        "status" => "pending",
        "expires_at"=>now()->addDay(7),
        'accept_at' => now()->subDay(1),
    ]);
    $encrypt = Crypt::encryptString($token);
    $response = $this->withHeaders([
        'Origin' => 'http://127.0.0.1',
    ])->getJson('/api/invitation/accept/'.$encrypt);
    $response->assertRedirect(config('app.frontend_url').'/expiration');
});
