<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Central application settings: contact details and article generation
 * defaults. All values are validated at the trust boundary.
 */
class SettingsController extends Controller
{
    public function index(): View
    {
        return view('admin.settings', [
            'settings' => collect(config('varilevke.setting_keys'))
                ->mapWithKeys(fn (array $def, string $key): array => [
                    $key => ['value' => Setting::get($key, (string) $def['default'] ?? null), ...$def],
                ]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = [];

        foreach (config('varilevke.setting_keys') as $key => $def) {
            $rules[$key] = $def['rules'];
        }

        $data = $request->validate($rules);

        foreach (config('varilevke.setting_keys') as $key => $def) {
            $value = (string) ($data[$key] ?? '');

            if ($key === 'whatsapp_numbers') {
                $value = implode(',', array_values(array_intersect(
                    config('contacts.numbers'),
                    array_map('trim', explode(',', $value))
                )));
            }

            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return back()->with('status', 'Settings updated.');
    }
}
