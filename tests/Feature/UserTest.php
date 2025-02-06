<?php

use App\Models\User;
use App\Notifications\EmailChangeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);


test('show-user',function(){
    $user = User::factory()->create();

    // 2. Simule une connexion
    $this->actingAs($user);
    $response = $this->withHeaders([
        'Origin' => 'http://127.0.0.1:8000',
    ])->getJson('/api/user/show');
    expect($response->status())->toBe(200);
});

test('update-user',function(){
    $user = User::factory()->create();
    $this->actingAs($user);
    $data = [
        "lastName" => "Ciszewicz",
        "firstName" => "Romain",
    ];
    $response = $this->withHeaders([
        'Origin' => 'http://127.0.0.1:8000',
    ])->putJson('/api/user/update',$data);
    expect($response->status())->toBe(200);
    //Vérifier que l'utilisateur est toujours connecté
    $this->assertTrue(Auth::check()); // L'utilisateur est bien authentifir
    $this->assertEquals($user->id, Auth::id()); // L'utilisateur connecté est bien celui qui a été simulé
});

test('destroy-user',function(){
    $user = User::factory()->create();
    $this->actingAs($user);
    $response = $this->withHeaders([
        'Origin' => 'http://127.0.0.1:8000',
    ])->deleteJson('/api/user/delete');
    expect($response->status())->toBe(200);
    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
     $this->refreshApplication();
     $this->assertGuest();
});

test('update-password-user',function(){
    $user = User::factory()->create([
        'password' => Hash::make('S3cr3t@e32'),
    ]);
    $this->actingAs($user);
    $response = $this->withHeaders([
        'Origin' => 'http://127.0.0.1:8000',
    ])->putJson('/api/user/update/password',[
        "lastPassword" =>"S3cr3t@e32",
        "password" => "Tato923@!!",
        "password_confirmation" => "Tato923@!!",
    ]);
    expect($response->status())->toBe(200);
    $user->refresh();
    $this->assertFalse(Hash::check('S3cr3t@e32', $user->password));
    $this->assertTrue(Hash::check('Tato923@!!', $user->password));
});

test('update-email-user',function(){
    Notification::fake();
    $user = User::factory()->create();
    $this->actingAs($user);
    $newEmail = "test@gmail.com";
    $response = $this->withHeaders([
        "Origin" => "http://127.0.0.1:8000",
    ])->patchJson('api/user/update/email',[
        "email" => "test@gmail.com",
    ]);
    expect($response->status())->toBe(200);
    Notification::assertSentOnDemand(
        EmailChangeNotification::class,
        function ($notification, $channels, $notifiable) use ($newEmail, $user) {
            // Vérifie que la notification a bien été envoyée par mail et à l'email correct
            return $notifiable->routes['mail'] === $newEmail && in_array('mail', $channels);
        }
    );
});
