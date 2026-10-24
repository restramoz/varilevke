<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'site_name' => 'Varilevke',
            'site_tagline' => 'Pelatihan K3 dan jasa lingkungan kerja',
            // Contact numbers shown in public pages. Editing this list must
            // never add or drop a number — WhatsApp::numbers() filters against
            // config('contacts.numbers').
            'whatsapp_numbers' => implode(',', config('contacts.numbers')),
        ];

        foreach ($defaults as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
