<?php

use App\Jobs\SendVerificationEmail;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Carbon\Factory;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('register', function () {
    // Fake les queues pour empêcher l'exécution des jobs
    Queue::fake();

    // Simuler l'envoi de données pour l'enregistrement
    $response = $this->withHeaders([
        'Origin' => 'http://127.0.0.1:8000',
    ])->postJson('/api/auth/register', [
        "lastName" => "Doe",
        "firstName" => "John",
        "email" => "john@gmail.com",
        "password" => "S3cr3t@e",
        "password_confirmation" => 'S3cr3t@e'
    ]);

    // Si la réponse contient des erreurs, afficher les erreurs pour déboguer
    if ($response->json('errors')) {
        dump($response->json('errors'));
    }
    dump($response->json());
    expect($response->status())->toBe(201);

    // Vérifier que l'utilisateur est bien créé dans la base de données
    $this->assertDatabaseHas('users', [
        'email' => 'john@gmail.com'
    ]);
    
    // Vérifier qu'un job a bien été dispatché
    Queue::assertPushed(SendVerificationEmail::class, function ($job) {
        return $job->user->email === 'john@gmail.com';
    });
});


test('login', function () {
    // Créer un utilisateur de test
    $user = User::create([
        'email' => 'john@gmail.com',
        'password' => Hash::make('S3cr3t@e'), // Assurez-vous de hasher le mot de passe
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);
    // Tester la connexion
    $response = $this->withHeaders([
        'Origin' => 'http://127.0.0.1:8000',
    ])->postJson('/api/auth/login', [
        "email" => "john@gmail.com",
        "password" => "S3cr3t@e",
        "remember" => true, 
    ]);
    // Vérification des erreurs dans la réponse
    if ($response->json('errors')) {
        dump($response->json('errors'));
    }

    // Vérification que le statut est 200
    expect($response->status())->toBe(200);
});


test('logout', function () {
    // 1. Crée un utilisateur
    $user = User::factory()->create();

    // 2. Simule une connexion
    $this->actingAs($user);
    $response = $this->withHeaders([
        'Origin' => 'http://127.0.0.1:8000',
    ])->postJson('/api/auth/logout');
    
    // 4. Vérifie que la réponse est correcte
    $response->assertStatus(200)
             ->assertJson([
                 'success' => true,
                 'message' => 'Déconnexion réussie',
             ]);

             $this->refreshApplication();
    // // 5. Vérifie que l'utilisateur est bien déconnecté
    $this->assertGuest();

    // Facultatif : Invalider explicitement la session
    session()->invalidate();
});


test('verifyEmail',function(){
    Queue::fake();

    $user = User::create([
        'email' => 'john@gmail.com',
        'password' => Hash::make('S3cr3t@e'), // Assurez-vous de hasher le mot de passe
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);
    
    $this->actingAs($user);

    $response = $this->withHeaders([
        'Origin' => 'http://127.0.0.1:8000',
    ])->postJson('/api/auth/email/verification-notification');
    dump($response->json("message"));
    expect($response->status())->toBe(200);
    Queue::assertPushed(SendVerificationEmail::class, function ($job) {
        return $job->user->email === 'john@gmail.com';
    });
    
});

test('forget-password',function(){
    Notification::fake();
    $user = User::create([
        'email' => 'john@gmail.com',
        'password' => Hash::make('S3cr3t@e'), // Assurez-vous de hasher le mot de passe
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    $response = $this->withHeaders([
        'Origin' => 'http://127.0.0.1:8000',
    ])->postJson('/api/auth/forget-password',[
        "email" => $user->email,
    ]);
    dump($response->json());
    expect($response->status())->toBe(200);
    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

test('reset-password',function(){
    Config::set('app.url', 'http://localhost:8000');
    Config::set('frontend_url', 'http://localhost:3000');

    $token = 'test-token';

    // Appeler la route pour reset-password
    $response = $this->get('/api/auth/reset-password/' . $token);

    // Vérifier la redirection
    $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
    $expectedUrl = $frontendUrl . '/reset-password?token=' . $token;

    $response->assertRedirect($expectedUrl);
});

test('post-reset-password',function(){
    Event::fake();
    $user = User::factory()->create([
        'password' => Hash::make('ancienMotDePasse'),
    ]);
   $token =  DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => Hash::make('test-token'),
        'created_at' => now(),
    ]);

    $this->assertDatabaseHas('password_reset_tokens', [
        'email' => $user->email,
    ]);
    $response = $this->withHeaders([
        'Origin' => 'http://127.0.0.1:8000',
    ])->postJson('/api/auth/reset-password',[
        "email" => $user->email,
        "token" =>'test-token',
        "password" => 'S3cr3t@e32',
        "password_confirmation" => 'S3cr3t@e32'
    ]);

    dump($response->json());
    expect($response->status())->toBe(200);
    $user->refresh();
    $this->assertFalse(Hash::check('ancienMotDePasse', $user->password));
    $this->assertTrue(Hash::check('S3cr3t@e32', $user->password));
   // Vérifier que l'événement PasswordReset a été déclenché
    Event::assertDispatched(PasswordReset::class, function ($event) use ($user) {
        return $event->user->is($user);
    });
});

test('show-user',function(){
    $user = User::factory()->create();

    // 2. Simule une connexion
    $this->actingAs($user);
    $response = $this->withHeaders([
        'Origin' => 'http://127.0.0.1:8000',
    ])->getJson('/api/user/show');

    dump($response->json());
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
    ])->postJson('/api/user/update',$data);

    dump($response->json());
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
    // dump($response->json());
    expect($response->status())->toBe(200);
    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
    // dump(Auth::user());
    // dump($user);
     // acting as unauthenticated user
     $this->refreshApplication();
     $this->assertGuest();
});



