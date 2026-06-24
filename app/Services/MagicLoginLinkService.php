<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MagicLoginLinkService
{
    public function create(int $userId, string $redirectTo, ?int $ttlMinutes = null): string
    {
        $redirectPath = $this->toSafePath($redirectTo);

        if ($redirectPath === '' || !Schema::hasTable('magic_login_tokens')) {
            return $redirectPath !== '' ? url($redirectPath) : url('/login');
        }

        $token = Str::random(80);
        $ttl = $ttlMinutes ?: (int) env('MAGIC_LOGIN_TTL_MINUTES', 10080);

        DB::table('magic_login_tokens')->insert([
            'user_id'       => $userId,
            'token_hash'    => hash('sha256', $token),
            'redirect_path' => $redirectPath,
            'expires_at'    => now()->addMinutes(max(5, $ttl)),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return route('autologin', [
            'token' => $token,
            'redirect' => $redirectPath,
        ]);
    }

    private function toSafePath(string $target): string
    {
        $target = trim($target);
        if ($target === '') {
            return '';
        }

        if (strpos($target, '://') !== false) {
            $parts = parse_url($target);
            $path = $parts['path'] ?? '/';
            $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';
            $target = $path . $query;
        }

        if (strpos($target, '//') === 0 || substr($target, 0, 1) !== '/') {
            return '';
        }

        return $target;
    }
}
