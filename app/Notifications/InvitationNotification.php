<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Crypt;

class InvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public int $tries = 3; // Nombre de tentatives max
    public int $backoff = 5; // Attente de 10 secondes avant de réessayer
    private string $token;
    private ?User $user=null;
    private Project $project;
    private User $invite;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $token,?User $user,Project $project,User $invite)
    {
        $this->token = $token;
        $this->user = $user;
        $this->project = $project;
        $this->invite = $invite;
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
            ->subject('Invitation à rejoindre un projet')
            ->greeting("Bonjour " . ($this->user->last_name ?? "cher utilisateur") . "!")
            ->line("Vous avez été invité à rejoindre le projet **{$this->project->title}**, de la part de **{$this->invite->first_name} {$this->invite->last_name}** !")
            ->line("Pour accepter l'invitation, cliquez sur le bouton ci-dessous.")
            ->action('Rejoindre le projet', config('app.url').'api/invitation/accept/'.Crypt::encryptString($this->token))
            ->line("Si vous n'êtes pas à l'origine de cette invitation, ignorez cet email.");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        if($this->user){
            return [
                'project_id' => $this->project->id,
                'project_name' => $this->project->title,
                'inviter_name' => $this->invite->last_name,
                'message' => "Vous avez été invité à rejoindre le projet : {$this->project->title}",
            ];
        }else{
            return [];
        }

    }
    public function failed($exception): void
    {
        \Log::error("Échec de l'envoi de l'invitation : " . $exception->getMessage());
    }
}
