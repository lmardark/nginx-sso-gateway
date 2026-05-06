<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

describe('SettingsController', function () {
    describe('edit', function () {
        it('renders settings page for admin', function () {
            $response = $this->actingAs($this->admin)->get(route('admin.settings.edit'));

            $response->assertOk();
            $response->assertInertia(fn ($page) => $page
                ->component('Admin/Settings')
                ->has('currentSettings')
            );
        });

        it('denies access to non-admin', function () {
            $user = User::factory()->create();

            $this->actingAs($user)->get(route('admin.settings.edit'))->assertForbidden();
        });
    });

    describe('update', function () {
        it('saves login settings', function () {
            $this->actingAs($this->admin)->put(route('admin.settings.update'), [
                'login_app_name'      => 'Meu SSO',
                'login_show_logo'     => true,
                'login_primary_color' => '#123456',
                'login_custom_css'    => 'body { color: red; }',
                'login_bg_color'      => '#ffffff',
            ]);

            expect(Setting::get('login_app_name'))->toBe('Meu SSO');
            expect(Setting::get('login_show_logo'))->toBe('1');
            expect(Setting::get('login_primary_color'))->toBe('#123456');
            expect(Setting::get('login_custom_css'))->toBe('body { color: red; }');
            expect(Setting::get('login_bg_color'))->toBe('#ffffff');
        });

        it('redirects with success flash after saving', function () {
            $response = $this->actingAs($this->admin)->put(route('admin.settings.update'), [
                'login_app_name'  => 'Test',
                'login_show_logo' => false,
            ]);

            $response->assertRedirect(route('admin.settings.edit'));
            $response->assertSessionHas('success');
        });

        it('saves show_logo as 0 when false', function () {
            $this->actingAs($this->admin)->put(route('admin.settings.update'), [
                'login_show_logo' => false,
            ]);

            expect(Setting::get('login_show_logo'))->toBe('0');
        });

        it('uploads a custom logo', function () {
            Storage::fake('public');
            $file = UploadedFile::fake()->create('logo.png', 100, 'image/png');

            $this->actingAs($this->admin)->put(route('admin.settings.update'), [
                'login_show_logo' => true,
                'login_logo'      => $file,
            ]);

            $path = Setting::get('login_logo_path');
            expect($path)->not->toBeNull();
            Storage::disk('public')->assertExists($path);
        });

        it('removes logo when login_logo_remove is true', function () {
            Storage::fake('public');
            $file = UploadedFile::fake()->create('logo.png', 100, 'image/png');

            $this->actingAs($this->admin)->put(route('admin.settings.update'), [
                'login_show_logo' => true,
                'login_logo'      => $file,
            ]);

            $existingPath = Setting::get('login_logo_path');
            expect($existingPath)->not->toBeNull();

            $this->actingAs($this->admin)->put(route('admin.settings.update'), [
                'login_show_logo'    => true,
                'login_logo_remove'  => true,
            ]);

            expect(Setting::get('login_logo_path'))->toBeNull();
            Storage::disk('public')->assertMissing($existingPath);
        });

        it('deletes old logo when a new one is uploaded', function () {
            Storage::fake('public');

            $this->actingAs($this->admin)->put(route('admin.settings.update'), [
                'login_show_logo' => true,
                'login_logo'      => UploadedFile::fake()->create('old.png', 100, 'image/png'),
            ]);
            $oldPath = Setting::get('login_logo_path');

            $this->actingAs($this->admin)->put(route('admin.settings.update'), [
                'login_show_logo' => true,
                'login_logo'      => UploadedFile::fake()->create('new.png', 100, 'image/png'),
            ]);

            Storage::disk('public')->assertMissing($oldPath);
            expect(Setting::get('login_logo_path'))->not->toBe($oldPath);
        });

        it('rejects invalid color hex', function () {
            $response = $this->actingAs($this->admin)->put(route('admin.settings.update'), [
                'login_show_logo'     => true,
                'login_primary_color' => 'not-a-color',
            ]);

            $response->assertSessionHasErrors('login_primary_color');
        });

        it('rejects logo larger than 2MB', function () {
            Storage::fake('public');
            $file = UploadedFile::fake()->create('huge.png', 3000, 'image/png');

            $response = $this->actingAs($this->admin)->put(route('admin.settings.update'), [
                'login_show_logo' => true,
                'login_logo'      => $file,
            ]);

            $response->assertSessionHasErrors('login_logo');
        });

        it('denies access to non-admin', function () {
            $user = User::factory()->create();

            $this->actingAs($user)->put(route('admin.settings.update'), [
                'login_show_logo' => true,
            ])->assertForbidden();
        });
    });
});
