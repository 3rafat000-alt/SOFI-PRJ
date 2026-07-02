<?php

namespace Database\Factories;

use App\Models\Governorate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class GovernorateFactory extends Factory
{
    protected $model = Governorate::class;

    public function definition(): array
    {
        $names = ['دمشق', 'حلب', 'حمص', 'اللاذقية', 'طرطوس', 'حماة', 'إدلب', 'دير الزور', 'الحسكة', 'الرقة', 'درعا', 'السويداء', 'القنيطرة', 'ريف دمشق'];
        $name = $this->faker->randomElement($names);

        return [
            'name_ar'          => $name,
            'name_en'          => Str::slug($name),
            'slug'             => Str::slug($name . '-' . fake()->unique()->bothify('##')),
            'lat'              => fake()->latitude(32.3, 37.0),
            'lng'              => fake()->longitude(35.5, 42.0),
            'properties_count' => 0,
        ];
    }
}
