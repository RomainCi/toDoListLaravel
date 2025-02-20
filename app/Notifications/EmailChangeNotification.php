<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class EmailChangeNotification extends Notification implements ShouldQueue
{
    use Queueable;
    protected User $user;
    /**
     * Create a new notification instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
        ->subject('Changement d\'adresse e-mail')  // Sujet de l'email
        ->greeting('Bonjour ' . $this->user->last_name)  // Salutation
        ->lines([
            'Nous avons reçu une demande de changement de votre adresse e-mail.',
            'Veuillez cliquer sur le bouton ci-dessous pour confirmer le changement.'
        ])
        // Le bouton d'action avec l'URL pour confirmer
        ->action('Confirmer le changement', $this->verifyRoute($notifiable))
        ->line("Si vous n'êtes pas à l'origine de cette demande, aucune action supplémentaire n'est requise.")
        // Salutation finale
        ->salutation('Cordialement, Votre équipe ' . config('app.name'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }

    protected function verifyRoute(AnonymousNotifiable $notifiable): string
    {
        return URL::temporarySignedRoute('user.email.change.verify', 60 * 20, [
            'user' => $this->user->id,
            'email' => $notifiable->routes['mail']
        ]);
    }
}
