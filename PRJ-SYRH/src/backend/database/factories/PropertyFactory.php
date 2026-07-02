<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Agent;
use App\Models\Governorate;
use App\Models\Area;
use App\Models\Property;
use App\Models\PropertyType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        $type = PropertyType::factory();
        $gov  = Governorate::factory();
        $area = Area::factory()->for($gov, 'governorate');

        $titleAr = fake('ar_SA')->sentence(3) . ' عقار';

        return [
            'ref_code'        => 'SYR-' . fake()->unique()->bothify('#######'),
            'property_type_id'=> $type,
            'agency_id'       => Agency::factory(),
            'agent_id'        => Agent::factory(),
            'governorate_id'  => $gov,
            'area_id'         => $area,
            'purpose'         => fake()->randomElement(['sale', 'rent']),
            'status'          => fake()->randomElement(['available', 'draft', 'sold', 'rented']),
            'title_ar'        => $titleAr,
            'title_en'        => fake()->sentence(3),
            'slug'            => Str::slug($titleAr . '-' . fake()->unique()->bothify('##')),
            'description_ar'  => fake('ar_SA')->realText(300),
            'description_en'  => fake()->realText(300),
            'price'           => fake()->randomFloat(2, 30000, 500000),
            'currency'        => fake()->randomElement(['USD', 'SYP']),
            'rent_period'     => fake()->randomElement(['month', 'year']),
            'area_sqm'        => fake()->numberBetween(50, 500),
            'bedrooms'        => fake()->numberBetween(1, 6),
            'bathrooms'       => fake()->numberBetween(1, 5),
            'parking'         => fake()->numberBetween(0, 3),
            'floor'           => fake()->numberBetween(0, 15),
            'year_built'      => fake()->numberBetween(1990, 2025),
            'furnished'       => fake()->boolean(),
            'address_ar'      => fake('ar_SA')->address(),
            'address_en'      => fake()->address(),
            'lat'             => fake()->latitude(33.3, 33.6),
            'lng'             => fake()->longitude(36.1, 36.4),
            'is_featured'     => fake()->boolean(20),
            'is_hot_deal'     => fake()->boolean(10),
            'views_count'     => fake()->numberBetween(0, 5000),
            'published_at'    => now()->subDays(fake()->numberBetween(0, 60)),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'       => 'available',
            'published_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }
}
