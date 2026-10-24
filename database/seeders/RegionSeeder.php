<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Kecamatan;
use App\Support\Slug;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        $path = (string) config('regional.data_path');

        $rows = json_decode((string) @file_get_contents($path), true);

        if (! is_array($rows) || $rows === []) {
            $this->command?->warn("Region data file missing or invalid: {$path}");

            return;
        }

        foreach ((array) config('regional.seed_provinces') as $province) {
            $this->seedProvince((string) $province, collect($rows));
        }
    }

    private function seedProvince(string $province, Collection $rows): void
    {
        $provinceRows = $rows->where('Provinsi', $province);

        if ($provinceRows->isEmpty()) {
            $this->command?->warn("Province not found in region data: {$province}");

            return;
        }

        $cityTakenNames = City::query()->pluck('name');

        foreach ($provinceRows->groupBy('Kode Kab/Kota') as $kotaCode => $cityRows) {
            $cityRows = collect($cityRows);
            $first = $cityRows->first();
            $name = (string) $first['Kabupaten/Kota'];
            $classification = (string) $first['Jenis Wilayah'];

            $city = City::query()->firstOrNew(['bps_code' => (string) $kotaCode]);
            $city->fill([
                'name' => $name,
                'slug' => $city->slug ?: Slug::unique(
                    Str::headline($classification).' '.$name,
                    'kota-'.((string) $kotaCode),
                    fn (string $candidate): bool => City::query()->where('slug', $candidate)->where('id', '!=', (int) $city->id)->exists()
                ),
                'province' => $province,
                'classification' => $classification,
                'bps_code' => (string) $kotaCode,
            ]);
            $city->save();

            $kecamatanTaken = Kecamatan::query()->where('city_id', $city->id)->pluck('name');

            foreach ($cityRows->unique('Kode Kecamatan') as $kecamatanRow) {
                $kecamatanName = (string) $kecamatanRow['Kecamatan'];
                $kecamatanCode = (string) $kecamatanRow['Kode Kecamatan'];

                if ($kecamatanTaken->contains($kecamatanName)) {
                    continue;
                }

                Kecamatan::query()->create([
                    'city_id' => $city->id,
                    'name' => $kecamatanName,
                    'slug' => Slug::unique(
                        $kecamatanName,
                        'kecamatan-'.$kecamatanCode,
                        fn (string $candidate): bool => Kecamatan::query()
                            ->where('city_id', $city->id)
                            ->where('slug', $candidate)
                            ->exists()
                    ),
                    'bps_code' => $kecamatanCode !== '' ? $kecamatanCode : null,
                ]);

                $kecamatanTaken->push($kecamatanName);
            }

            $city->forceFill(['kecamatan_count' => Kecamatan::query()->where('city_id', $city->id)->count()])->save();
        }

        $this->command?->info("Province seeded: {$province} (".City::query()->where('province', $province)->count().' cities)');
    }
}
