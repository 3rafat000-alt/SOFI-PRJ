<?php

namespace Database\Factories;

use App\Models\UserNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserNotificationFactory extends Factory
{
    protected $model = UserNotification::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(),
            'body' => $this->faker->paragraph(),
            'template_code' => 'test',
            'channel' => 'push',
            'data' => [],
            'status' => 'sent',
            'sent_at' => now(),
        ];
    }
}
