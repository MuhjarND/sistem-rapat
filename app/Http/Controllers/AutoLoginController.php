<?php

namespace App\Http\Controllers;

use App\Services\ChatbotGatewayService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AutoLoginController extends Controller
{
    public function show(Request $request)
    {
        $token = $this->tokenFromRequest($request);
        $redirect = $this->safeRedirectPath((string) $request->query('redirect', ''));

        if ($token === '') {
            return $this->errorResponse();
        }

        return response()
            ->view('auth.autologin-continue', compact('token', 'redirect'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function login(Request $request, ChatbotGatewayService $gateway)
    {
        $token = (string) $request->input('token', '');
        $redirect = $this->safeRedirectPath((string) $request->input('redirect', ''));

        if ($token === '') {
            return $this->errorResponse();
        }

        $localValidation = $this->validateInternalToken($token);
        if (!empty($localValidation['valid']) && !empty($localValidation['app_user_id'])) {
            $user = $this->findUser((string) $localValidation['app_user_id']);

            if (!$user) {
                return $this->errorResponse();
            }

            $guard = config('auth.defaults.guard', 'web');
            Auth::guard($guard)->login($user);
            $request->session()->regenerate();

            $tokenRedirect = $this->safeRedirectPath((string) ($localValidation['redirect_path'] ?? ''));

            Log::info('Internal magic login user matched', [
                'user_id' => $user->id,
                'role' => $user->role ?? null,
                'guard' => $guard,
            ]);

            return redirect($redirect ?: $tokenRedirect ?: $this->redirectPathFor($user));
        }

        $validation = $gateway->validateMagicToken($token);

        if (empty($validation['valid']) || empty($validation['app_user_id'])) {
            if (Auth::guard(config('auth.defaults.guard', 'web'))->check()) {
                return redirect($redirect ?: $this->redirectPathFor(Auth::user()));
            }

            return $this->errorResponse();
        }

        $appUserId = (string) $validation['app_user_id'];
        Log::info('Chatbot magic login user lookup started', [
            'app_user_id_hash' => substr(hash('sha256', $appUserId), 0, 16),
            'app_user_id_length' => strlen($appUserId),
            'app_user_id_is_numeric' => is_numeric($appUserId),
        ]);

        $user = $this->findUser($appUserId);

        if (!$user) {
            Log::warning('Chatbot magic login user not found', [
                'app_user_id_hash' => substr(hash('sha256', $appUserId), 0, 16),
            ]);

            return $this->errorResponse();
        }

        $guard = config('auth.defaults.guard', 'web');
        Auth::guard($guard)->login($user);
        $request->session()->regenerate();

        Log::info('Chatbot magic login user matched', [
            'user_id' => $user->id,
            'role' => $user->role ?? null,
            'guard' => $guard,
        ]);

        return redirect($redirect ?: $this->redirectPathFor($user));
    }

    private function findUser(string $appUserId)
    {
        $query = User::query()->where('id', $appUserId);

        if (Schema::hasColumn('users', 'app_user_id')) {
            $query->orWhere('app_user_id', $appUserId);
        }

        if (filter_var($appUserId, FILTER_VALIDATE_EMAIL)) {
            $query->orWhere('email', $appUserId);
        }

        $user = $query->first();

        if ($user && Schema::hasColumn('users', 'is_active') && isset($user->is_active) && (int) $user->is_active !== 1) {
            return null;
        }

        return $user;
    }

    private function validateInternalToken(string $token): array
    {
        if (!Schema::hasTable('magic_login_tokens')) {
            return ['valid' => false, 'reason' => 'table_missing'];
        }

        $row = DB::table('magic_login_tokens')
            ->where('token_hash', hash('sha256', $token))
            ->where('expires_at', '>=', now())
            ->first();

        if (!$row) {
            return ['valid' => false, 'reason' => 'not_found_or_expired'];
        }

        DB::table('magic_login_tokens')->where('id', $row->id)->update([
            'last_used_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'valid' => true,
            'app_user_id' => (string) $row->user_id,
            'redirect_path' => (string) ($row->redirect_path ?? ''),
        ];
    }

    private function redirectPathFor($user): string
    {
        switch ($user->role ?? null) {
            case 'admin':
            case 'operator':
                return '/dashboard';
            case 'notulis':
                return '/notulensi/dashboard';
            case 'peserta':
                return '/peserta/dashboard';
            case 'approval':
                return '/approval';
            case 'protokoler':
                return '/agenda-pimpinan';
            default:
                return '/home';
        }
    }

    private function safeRedirectPath(string $path): string
    {
        if ($path === '') {
            return '';
        }

        $decoded = rawurldecode($path);

        if (strpos($decoded, '://') !== false || strpos($decoded, '//') === 0) {
            return '';
        }

        if (substr($decoded, 0, 1) !== '/') {
            return '';
        }

        $allowedPrefixes = [
            '/dashboard',
            '/notulensi',
            '/peserta',
            '/approval',
            '/agenda-pimpinan',
            '/undangan-saya',
            '/absensi-saya',
            '/absensi',
            '/rapat',
            '/laporan',
            '/home',
        ];

        foreach ($allowedPrefixes as $prefix) {
            if ($decoded === $prefix || strpos($decoded, $prefix . '/') === 0 || strpos($decoded, $prefix . '?') === 0) {
                return $decoded;
            }
        }

        return '';
    }

    private function errorResponse()
    {
        return response()
            ->view('auth.autologin-error', [
                'message' => config('chatbot.autologin_error_message'),
            ], 401);
    }

    private function tokenFromRequest(Request $request)
    {
        $rawQuery = (string) $request->server('QUERY_STRING', '');

        foreach (explode('&', $rawQuery) as $part) {
            if ($part === '') {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $part, 2), 2, '');

            if (rawurldecode($key) === 'token') {
                return rawurldecode($value);
            }
        }

        return (string) $request->query('token', '');
    }
}
