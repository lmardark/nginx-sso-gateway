<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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
        $user->nickname = $validated['nickname'] !== '' ? ($validated['nickname'] ?? null) : null;

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        return redirect()->route('profile.edit')
            ->with('success', 'Perfil atualizado com sucesso.');
    }
}
