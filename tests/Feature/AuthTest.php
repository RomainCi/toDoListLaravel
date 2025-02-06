<?php
use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Jobs\SendVerificationEmail;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Cookie\CookieJar;

uses(RefreshDatabase::class);


test('register-50-user-dif', function() {
    Queue::fake();
    
    $client = new Client(); // Initialisation du client Guzzle
    $responses = [];
    $emailBase = 'testuser'; // Base pour générer des emails uniques
    $promises = []; // Liste des promesses (requêtes asynchrones)
    $cookieJar = new CookieJar(); // Créer un gestionnaire de cookies
    $client = new Client(['cookies' => $cookieJar]);
    // Envoi des 50 requêtes en parallèle
    for ($i = 1; $i <= 50; $i++) {
        $client->get('http://127.0.0.1:8000/api/csrf-cookie', [
            'headers' => [
                'Origin' => 'http://127.0.0.1:8000',
            ]
        ]);

        // Récupérer le cookie CSRF (XSRF-TOKEN) depuis le gestionnaire de cookies
        $cookies = $cookieJar->toArray();
        $csrfToken = '';
        foreach ($cookies as $cookie) {
            if ($cookie['Name'] === 'XSRF-TOKEN') {
                $csrfToken = urldecode($cookie['Value']);
                break;
            }
        }

        // Récupérer le cookie CSRF depuis le client (cela dépend de ta gestion de session)
        // Remarque : il faut récupérer le cookie CSRF dans le client. Cela peut varier selon la méthode de session utilisée.

        // Exemple de récupération du cookie depuis les headers de la réponse
        $cookie = $client->getConfig('cookies')->getCookieByName('XSRF-TOKEN');
        $csrfToken = $cookie ? $cookie->getValue() : '';
        $email = $emailBase . $i . '@gmail.com'; // Email unique pour chaque utilisateur
        // Ajouter chaque requête à la liste des promesses
        $promises[] = $client->postAsync('http://127.0.0.1:8000/api/auth/register', [
            'json' => [
                "lastName" => "Doe",
                "firstName" => "John",
                "email" => $email,
                "password" => "3dazdzadD#",
                "password_confirmation" => '3dazdzadD#'
            ],
            'headers' => [
                'Origin' => 'http://127.0.0.1:8000',
                'X-XSRF-TOKEN' => urldecode($csrfToken),
            ]
        ]);
    }

    // Attendre que toutes les requêtes soient terminées
    $responses = Utils::settle($promises)->wait();

    // Vérifier la réponse et effectuer les assertions pour chaque utilisateur
    foreach ($responses as $response) {
        dd($response);
        // Vérifier que la réponse est correcte (réponse 201 pour la création)
        $this->assertEquals(201, $response['value']->getStatusCode());

        $responseData = json_decode($response['value']->getBody()->getContents(), true);
        $email = $responseData['data']['email'];

        // Vérifier que l'utilisateur est bien créé dans la base de données
        $this->assertDatabaseHas('users', [
            'email' => $email
        ]);

        // Vérifier qu'un job a bien été dispatché pour chaque utilisateur
        Queue::assertPushed(SendVerificationEmail::class, function ($job) use ($email) {
            return $job->user->email === $email;
        });
        dump($response->json());
    }
});


test('register-50-user',function(){
    Queue::fake();

        // Simuler l'envoi de données pour l'enregistrement de 50 utilisateurs
        $responses = [];
        $emailBase = 'testuser'; // Base pour générer des emails uniques

        for ($i = 1; $i <= 50; $i++) {
            $email = $emailBase . $i . '@gmail.com'; // Email unique pour chaque utilisateur
            $response = $this->withHeaders([
                'Origin' => 'http://127.0.0.1:8000',
            ])->postJson('/api/auth/register', [
                "lastName" => "Doe",
                "firstName" => "John",
                "email" => $email,
                "password" => "3dazdzadD#",
                "password_confirmation" => '3dazdzadD#'
            ]);
            
            // Si la réponse contient des erreurs, afficher les erreurs pour déboguer
            if ($response->json('errors')) {
                dump($response->json('errors'));
            }
            
           

            $responses[] = $response;
            // dd($response->json('data'));

            // Vérifier que l'utilisateur est bien créé dans la base de données
            $this->assertDatabaseHas('users', [
                'email' => $email
            ]);
            Queue::assertPushed(SendVerificationEmail::class, function ($job)use ($email) {
                return $job->user->email === $email;
            });
        }
});

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
        "password" => "3dazdzadD#",
        "password_confirmation" => '3dazdzadD#'
    ]);

    // Si la réponse contient des erreurs, afficher les erreurs pour déboguer
    if ($response->json('errors')) {
        dump($response->json('errors'));
    }
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
                "data"=>[
                 'success' => true,
                 'message' => 'Déconnexion réussie',
                ],
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
    ])->patchJson('/api/auth/reset-password',[
        "email" => $user->email,
        "token" =>'test-token',
        "password" => 'S3cr3t@e32',
        "password_confirmation" => 'S3cr3t@e32'
    ]);
    expect($response->status())->toBe(200);
    $user->refresh();
    $this->assertFalse(Hash::check('ancienMotDePasse', $user->password));
    $this->assertTrue(Hash::check('S3cr3t@e32', $user->password));
   // Vérifier que l'événement PasswordReset a été déclenché
    Event::assertDispatched(PasswordReset::class, function ($event) use ($user) {
        return $event->user->is($user);
    });
});