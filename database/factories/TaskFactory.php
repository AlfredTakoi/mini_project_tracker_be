<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Task;
use App\Models\Project;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition()
    {
        return [
            'name' => $this->faker->sentence(4),
            'status' => 'draft',
            'weight' => $this->faker->numberBetween(1, 10),
            'project_id' => Project::factory(),
        ];
    }
}
