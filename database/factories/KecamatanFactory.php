<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Kecamatan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Kecamatan> */
class KecamatanFactory extends Factory
{
    protected $model = Kecamatan::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->streetName();

        return [
            'city_id' => City::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'bps_code' => (string) $this->faker->unique()->numberBetween(1100000, 9999999),
        ];
    }
}
