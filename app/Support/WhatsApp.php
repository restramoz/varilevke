<?php

namespace App\Support;

use App\Models\Setting;

final class WhatsApp
{
    /**
     * The four configured contacts, deduplicated, order preserved. Numbers are
     * strings: leading zeros must never be cast away. DB settings may tighten
     * but never grow this list.
     *
     * @return list<non-empty-string>
     */
    public static function numbers(): array
    {
        $configured = config('contacts.numbers', []);
        $extra = Setting::get('whatsapp_numbers');

        if (is_string($extra) && $extra !== '') {
            $configured = array_values(array_intersect($configured, array_map('trim', explode(',', $extra))));
        }

        return array_values(array_filter($configured, fn ($n): bool => is_string($n) && $n !== ''));
    }

    /**
     * One configured contact picked at random per call, so outbound CTAs are
     * distributed over the whole pool instead of always the first number.
     *
     * @return non-empty-string|''
     */
    public static function random(): string
    {
        $numbers = self::numbers();

        return $numbers === [] ? '' : $numbers[array_rand($numbers)];
    }

    /**
     * wa.me requires the international form: 08… → 62….
     */
    public static function normalize(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        return $digits;
    }

    public static function link(string $raw, ?string $message = null): string
    {
        $url = 'https://wa.me/'.self::normalize($raw);

        return $message === null
            ? $url
            : $url.'?text='.rawurlencode(mb_substr($message, 0, 300));
    }

    /**
     * Accessible grouped entries: raw number for display text, wa.me link for href.
     *
     * @return list<array{raw: string, href: string}>
     */
    public static function callout(): array
    {
        return array_map(
            fn (string $number): array => [
                'raw' => $number,
                'href' => self::link($number, 'Halo, saya ingin bertanya tentang layanan Anda.'),
            ],
            self::numbers()
        );
    }

    /**
     * Format number for display: 08118500177 → 0811-8500-177
     */
    public static function display(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if (strlen($digits) >= 10 && str_starts_with($digits, '08')) {
            return substr($digits, 0, 4).'-'.substr($digits, 4, 4).'-'.substr($digits, 8);
        }

        return $raw;
    }
}
