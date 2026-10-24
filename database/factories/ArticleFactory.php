<?php

namespace Database\Factories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Article> */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(4);
        $slug = str()->slug($title);
        $content = '<p>'.implode('</p><p>', [$this->faker->paragraph(), $this->faker->paragraph()]).'</p>';
        $wordCount = str_word_count(strip_tags($content));

        return [
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $this->faker->sentence(10),
            'content' => $content,
            'reading_time' => max(1, (int) ceil($wordCount / 200)),
            'word_count' => $wordCount,
            'status' => Article::STATUS_DRAFT,
            'seo_title' => $title,
            'meta_description' => $this->faker->sentence(12),
        ];
    }

    /** @return Factory<Article> */
    public function published(): Factory
    {
        return $this->state(fn (): array => [
            'status' => Article::STATUS_PUBLISHED,
            'published_at' => $this->faker->dateTimeThisYear(),
        ]);
    }
}
