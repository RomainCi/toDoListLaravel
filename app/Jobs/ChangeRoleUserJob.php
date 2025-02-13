<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\User;
use App\Notifications\ChangeRoleUserNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ChangeRoleUserJob implements ShouldQueue
{
    use Queueable;
    protected User $user;
    protected string $role;
    protected Project $project;

    /**
     * Create a new job instance.
     */
    public function __construct($project, $user, $role)
    {
        $this->project = $project;
        $this->user = $user;
        $this->role = $role;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->user->notify(new ChangeRoleUserNotification($this->project,$this->role,$this->user));
    }
}
