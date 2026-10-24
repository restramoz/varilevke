<?php

namespace Database\Factories;

use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<City> */
class CityFactory extends Factory
{
    protected $model = City::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->city();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'province' => $this->faker->unique()->state(),
            'classification' => $this->faker->randomElement(['Kota', 'Kabupaten']),
            'bps_code' => (string) $this->faker->unique()->numberBetween(100000, 999999),
            'kecamatan_count' => null,
        ];
    }
}
