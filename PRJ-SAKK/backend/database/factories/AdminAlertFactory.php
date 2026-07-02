<?php

namespace Database\Factories;

use App\Models\AdminAlert;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdminAlertFactory extends Factory
{
    protected $model = AdminAlert::class;

    public function definition(): array
    {
        return [
            'admin_id' => null,
            'title' => $this->faker->sentence(),
            'message' => $this->faker->paragraph(),
            'type' => 'info',
            'link' => null,
        ];
    }
}
