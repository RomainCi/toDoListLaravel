<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
//         User::factory(2)->create();
//         Project::factory(2)->create();
        ProjectUser::factory()->create([
            "user_id" => 2,
            "project_id" => 1,
            "role" => "visitor"
        ]);
    }
}
