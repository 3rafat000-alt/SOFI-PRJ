<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Governorate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AreaFactory extends Factory
{
    protected $model = Area::class;

    public function definition(): array
    {
        $name = fake()->city() . ' ' . fake()->randomElement(['الغربي', 'الشرقي', 'الجديد', 'القديم']);

        return [
            'governorate_id'   => Governorate::factory(),
            'name_ar'          => $name,
            'name_en'          => Str::slug($name),
            'slug'             => Str::slug($name . '-' . fake()->unique()->bothify('##')),
            'lat'              => fake()->latitude(33.3, 33.6),
            'lng'              => fake()->longitude(36.1, 36.4),
            'properties_count' => 0,
        ];
    }

    public function forGovernorate(Governorate $governorate): static
    {
        return $this->state(fn (array $attributes) => [
            'governorate_id' => $governorate->id,
        ]);
    }
}
