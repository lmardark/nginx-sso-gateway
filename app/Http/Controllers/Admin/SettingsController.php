<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class SettingsController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Admin/Settings', [
            'currentSettings' => Setting::loginSettings(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'login_app_name' => ['nullable', 'string', 'max:255'],
            'login_show_logo' => ['required', 'boolean'],
            'login_primary_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'login_custom_css' => ['nullable', 'string'],
        ]);

        Setting::set('login_app_name', $validated['login_app_name'] ?? null);
        Setting::set('login_show_logo', $validated['login_show_logo'] ? '1' : '0');
        Setting::set('login_primary_color', $validated['login_primary_color'] ?? null);
        Setting::set('login_custom_css', $validated['login_custom_css'] ?? null);

        return redirect()->route('admin.settings.edit')
            ->with('success', 'Configurações salvas com sucesso.');
    }
}
