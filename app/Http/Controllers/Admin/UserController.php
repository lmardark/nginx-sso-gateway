<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => User::query()->orderBy('created_at', 'desc')
                ->get(['id', 'username', 'nickname', 'created_at']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::query()->create($validated);

        ActivityLog::create([
            'actor_id' => Auth::id(),
            'event' => 'user_created',
            'target_username' => $user->username,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuário criado com sucesso.');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Admin/Users/Edit', [
            'user' => $user->only('id', 'username', 'created_at'),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users,username,'.$user->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->username = $validated['username'];

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        ActivityLog::create([
            'actor_id' => Auth::id(),
            'event' => 'user_updated',
            'target_username' => $user->username,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuário atualizado com sucesso.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $username = $user->username;
        $user->delete();

        ActivityLog::create([
            'actor_id' => Auth::id(),
            'event' => 'user_deleted',
            'target_username' => $username,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuário removido com sucesso.');
    }
}
