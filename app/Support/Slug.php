<?php

namespace App\Support;

use Illuminate\Support\Str;

final class Slug
{
    /**
     * Stable URL slug; falls back to a suffix derived from $seed only on a
     * collision, so names stay readable while catalog order guarantees truth.
     */
    public static function unique(string $name, string $seed, callable $exists): string
    {
        $base = (string) Str::slug($name) ?: 'item';

        if (! $exists($base)) {
            return $base;
        }

        $suffix = (string) Str::slug($seed) ?: (string) crc32($seed);
        $candidate = Str::limit($base, 190, '').'-'.$suffix;

        while ($exists($candidate)) {
            $candidate .= '-'.substr(md5($seed.microtime()), 0, 6);
        }

        return $candidate;
    }
}
