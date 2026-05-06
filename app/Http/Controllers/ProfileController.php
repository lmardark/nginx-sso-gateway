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
        $validated = $request->validate([
            'nickname' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();
        $user->nickname = ($validated['nickname'] ?? '') ?: null;

        if (! empty($validated['password'])) {
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
