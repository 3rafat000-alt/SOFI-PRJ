<?php

namespace Database\Factories;

use App\Models\PropertyType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PropertyTypeFactory extends Factory
{
    protected $model = PropertyType::class;

    public function definition(): array
    {
        $types = [
            ['شقة', 'Apartment'],
            ['فيلا', 'Villa'],
            ['منزل', 'House'],
            ['تجاري', 'Commercial'],
            ['أرض', 'Land'],
        ];
        $pair = $this->faker->randomElement($types);

        return [
            'name_ar' => $pair[0],
            'name_en' => $pair[1],
            'slug'    => Str::slug($pair[1]) . '-' . $this->faker->unique()->bothify('##'),
            'icon'    => 'building',
            'sort'    => $this->faker->numberBetween(1, 10),
        ];
    }
}
