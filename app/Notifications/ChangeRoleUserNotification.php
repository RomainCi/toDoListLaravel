<?php

namespace App\Notifications;

use App\Action\Translate;
use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChangeRoleUserNotification extends Notification implements ShouldQueue
{
    use Queueable;
    protected Project $project;
    protected string $role;
    protected User $user;
    /**
     * Create a new notification instance.
     */
    public function __construct(Project $project,string $role,User $user)
    {
        $this->project = $project;
        $this->role = $role;
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
        $translatedRole = (new Translate())($this->role); // Appel de la traduction
        return (new MailMessage)
            ->subject('Modification de role')
            ->greeting('Bonjour ' . $this->user->last_name)
            ->line('Votre rôle dans le projet '.$this->project->title.' a été modifié.')
            ->line('Votre nouveau rôle est : ' . $translatedRole)
            ->action('Voir votre projet', config('app.frontend_url').'/projet')
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
}
