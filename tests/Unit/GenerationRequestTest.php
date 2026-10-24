<?php

namespace Tests\Unit;

use App\Models\CatalogItem;
use App\Models\City;
use App\Models\Kecamatan;
use App\Services\Ai\GenerationException;
use App\Services\Ai\GenerationRequest;
use PHPUnit\Framework\TestCase;

class GenerationRequestTest extends TestCase
{
    public function test_target_defaults_to_eight_thousand_words(): void
    {
        self::assertSame(8000, (new GenerationRequest(topic: 'X'))->targetWords());
    }

    public function test_word_band_boundaries(): void
    {
        self::assertSame(8000, (new GenerationRequest(topic: 'X', wordCount: 8000))->targetWords());
        self::assertSame(10000, (new GenerationRequest(topic: 'X', wordCount: 10000))->targetWords());
    }

    public function test_word_count_below_minimum_is_rejected(): void
    {
        $this->expectException(GenerationException::class);
        $this->expectExceptionMessage('between 8000 and 10000');

        new GenerationRequest(topic: 'X', wordCount: 7999);
    }

    public function test_word_count_above_maximum_is_rejected(): void
    {
        $this->expectException(GenerationException::class);

        new GenerationRequest(topic: 'X', wordCount: 10001);
    }

    public function test_blank_topic_is_rejected(): void
    {
        $this->expectException(GenerationException::class);
        $this->expectExceptionMessage('Topic is required');

        new GenerationRequest(topic: '   ');
    }

    public function test_region_label_for_city(): void
    {
        $city = new City(['name' => 'Surabaya', 'slug' => 'kota-surabaya', 'province' => 'Jawa Timur', 'classification' => 'Kota']);

        $request = new GenerationRequest(topic: 'X', city: $city);

        self::assertSame('Kota Surabaya, Jawa Timur', $request->regionLabel());
    }

    public function test_region_label_for_kecamatan_includes_city(): void
    {
        $city = new City(['name' => 'Sidoarjo', 'slug' => 'kabupaten-sidoarjo', 'province' => 'Jawa Timur', 'classification' => 'Kabupaten']);
        $kecamatan = new Kecamatan(['name' => 'Taman', 'slug' => 'taman', 'city_id' => 1]);
        $kecamatan->setRelation('city', $city);

        $request = new GenerationRequest(topic: 'X', kecamatan: $kecamatan);

        self::assertSame('Taman, Kabupaten Sidoarjo, Jawa Timur', $request->regionLabel());
    }

    public function test_region_label_is_null_without_location(): void
    {
        $request = new GenerationRequest(topic: 'X', catalogItem: new CatalogItem(['name' => 'Ahli K3 Umum']));

        self::assertNull($request->regionLabel());
    }
}
