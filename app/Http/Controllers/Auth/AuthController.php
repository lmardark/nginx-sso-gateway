<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\ActivityLog;
use App\Services\Auth\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

final class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $isAuthenticated = $this->authService->autenticationFunction($request->validated());

        if ($isAuthenticated) {
            $request->session()->regenerate();

            ActivityLog::create([
                'actor_id' => Auth::id(),
                'event' => 'login_success',
                'target_username' => Auth::user()?->username,
                'ip_address' => $request->ip(),
            ]);

            $url = $request->input('return_to')
                           ?? session()->pull('url.intended')
                           ?? route('home');

            $allowedHost = (string) config('app.allowed_host_redirect', '');
            $host = parse_url($url, PHP_URL_HOST);
            $isSafe = $allowedHost !== ''
                && $host !== null
                && ($host === $allowedHost || str_ends_with($host, '.'.$allowedHost));
            $destination = $isSafe ? $url : route('home');

            return redirect()->away($destination);
        }

        ActivityLog::create([
            'actor_id' => null,
            'event' => 'login_failed',
            'target_username' => $request->input('username'),
            'ip_address' => $request->ip(),
        ]);

        return back()->withErrors([
            'username' => 'Usuário ou senha incorretos.',
        ]);
    }

    public function logout(): RedirectResponse
    {
        $username = Auth::user()?->username;
        $actorId = Auth::id();

        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        ActivityLog::create([
            'actor_id' => $actorId,
            'event' => 'logout',
            'target_username' => $username,
            'ip_address' => request()->ip(),
        ]);

        return redirect()->route('login');
    }
}
