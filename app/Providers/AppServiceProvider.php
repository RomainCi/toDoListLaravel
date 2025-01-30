<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Personnalisation de l'email de vérification
        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return (new MailMessage)
                ->subject('Confirmez votre adresse e-mail') // Changer l'objet du mail
                ->line('Merci de vous être inscrit sur notre site !') // Texte personnalisé
                ->line('Pour compléter votre inscription, veuillez confirmer votre adresse e-mail en cliquant sur le bouton ci-dessous.')
                ->action('Confirmer mon e-mail', $url) // Lien vers la vérification
                ->line('Si vous n’avez pas créé de compte, vous pouvez ignorer cet e-mail.');
        });
    }
}
