<?php

namespace Database\Factories;

use App\Models\CatalogItem;
use App\Support\Slug;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<CatalogItem> */
class CatalogItemFactory extends Factory
{
    protected $model = CatalogItem::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);
        $slug = Str::slug($name);

        return [
            'name' => $name,
            'slug' => $slug,
            'category' => CatalogItem::CATEGORY_PELATIHAN,
            'source_order' => $this->faker->unique()->numberBetween(1, 999),
            'review_required' => false,
            'review_note' => null,
        ];
    }

    /** @return Factory<CatalogItem> */
    public function category(string $category): Factory
    {
        return $this->state(fn (): array => [
            'category' => $category,
            'name' => $category === CatalogItem::CATEGORY_JASA ? 'Jasa '.$this->faker->unique()->words(3, true) : $this->faker->unique()->words(3, true),
            'slug' => function (array $attributes): string {
                return Slug::unique($attributes['name'], $attributes['category'].'-'.$attributes['source_order'],
                    fn (string $candidate): bool => CatalogItem::query()->where('slug', $candidate)->exists());
            },
        ]);
    }
}
