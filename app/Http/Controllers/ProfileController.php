<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ProfileController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Profile/Edit');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $changingPassword = ! empty($request->input('password'));

        $validated = $request->validate([
            'nickname' => ['nullable', 'string', 'max:255'],
            'current_password' => ($changingPassword && ! $user->is_admin) ? ['required', 'current_password'] : [],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->nickname = ($validated['nickname'] ?? '') ?: null;

        if ($changingPassword) {
            $user->password = $validated['password'];
        }

        $user->save();

        ActivityLog::create([
            'actor_id' => $user->id,
            'event' => 'profile_updated',
            'target_username' => $user->username,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('profile.edit')
            ->with('success', 'Perfil atualizado com sucesso.');
    }
}
