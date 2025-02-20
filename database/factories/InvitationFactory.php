<?php

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "project_id" => Project::factory(),
            "inviter_id"=> User::factory(),
            "token" => $this->faker->uuid(),
            "status" => "pending",
            "expires_at" => now()->addDays(7),
            "email"=>fake()->unique()->safeEmail(),
            "accept_at" => null,
        ];
    }
}
