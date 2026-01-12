<?php

namespace App\Helpers;

class NameHelper
{
    public static function withoutTitles($name, string $fallback = '-'): string
    {
        $value = trim((string) $name);
        if ($value === '') {
            return $fallback;
        }

        $parts = explode(',', $value);
        $base = trim((string) ($parts[0] ?? $value));

        $base = preg_replace('/^(?:(?:dr|drs|dra|ir|h|hj|prof|kh|k\.h)\.?\s+)+/i', '', $base);
        $base = trim($base);

        return $base !== '' ? $base : $fallback;
    }
}
