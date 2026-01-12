<?php

namespace App\Helpers;

use Carbon\Carbon;

class TimeHelper
{
    public static function short($time, string $fallback = '-'): string
    {
        $value = trim((string) $time);
        if ($value === '') {
            return $fallback;
        }

        try {
            return Carbon::parse($value)->format('H:i');
        } catch (\Throwable $e) {
            if (preg_match('/^\d{1,2}:\d{2}/', $value, $m)) {
                return str_pad($m[0], 5, '0', STR_PAD_LEFT);
            }
        }

        return $value;
    }
}
