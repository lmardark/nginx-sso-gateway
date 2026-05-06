<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Services\Auth\UsernameValidationService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class SetupController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Admin/Setup');
    }

    public function store(Request $request): RedirectResponse
    {
        $validationType = (string) $request->input('validation_type', '');
        $customPattern = (string) $request->input('custom_pattern', '');

        $usernameRules = [
            'required', 'string', 'max:255', 'unique:users,username',
            ...UsernameValidationService::buildRules($validationType, $customPattern),
        ];

        $validated = $request->validate([
            'nickname' => ['nullable', 'string', 'max:255'],
            'validation_type' => ['required', 'in:cpf,celular,personalizado'],
            'custom_pattern' => [
                Rule::requiredIf($validationType === 'personalizado'),
                'nullable',
                'string',
                static function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    if (@preg_match('~'.$value.'~', '') === false) {
                        $fail('Expressão regular inválida.');
                    }
                },
            ],
            'username' => $usernameRules,
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = DB::transaction(function () use ($validated) {
            if (User::query()->exists()) {
                abort(403);
            }

            return User::query()->create([
                'nickname' => $validated['nickname'] ?? null,
                'username' => $validated['username'],
                'password' => $validated['password'],
                'is_admin' => true,
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();

        Setting::set('username_validation_type', $validationType);
        Setting::set('username_custom_pattern', $customPattern);

        return redirect()->route('home');
    }
}
