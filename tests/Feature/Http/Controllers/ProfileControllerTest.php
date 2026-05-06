<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->user = User::factory()->create(['nickname' => 'OldNick', 'password' => Hash::make('currentpass123')]);
});

describe('ProfileController', function () {
    describe('edit', function () {
        it('renders profile edit page for authenticated user', function () {
            $response = $this->actingAs($this->user)->get(route('profile.edit'));

            $response->assertOk();
            $response->assertInertia(fn ($page) => $page->component('Profile/Edit'));
        });

        it('requires authentication', function () {
            $this->get(route('profile.edit'))->assertRedirectContains(route('login'));
        });
    });

    describe('update', function () {
        it('updates nickname', function () {
            $this->actingAs($this->user)->put(route('profile.update'), [
                'nickname' => 'NewNick',
            ]);

            expect($this->user->fresh()->nickname)->toBe('NewNick');
        });

        it('clears nickname when empty', function () {
            $this->actingAs($this->user)->put(route('profile.update'), [
                'nickname' => '',
            ]);

            expect($this->user->fresh()->nickname)->toBeNull();
        });

        it('does not change password when left blank', function () {
            $oldHash = $this->user->password;

            $this->actingAs($this->user)->put(route('profile.update'), [
                'nickname' => 'Test',
            ]);

            expect($this->user->fresh()->password)->toBe($oldHash);
        });

        it('fails when passwords do not match', function () {
            $response = $this->actingAs($this->user)->put(route('profile.update'), [
                'current_password'      => 'currentpass123',
                'password'              => 'newpassword123',
                'password_confirmation' => 'different123',
            ]);

            $response->assertSessionHasErrors('password');
        });

        it('redirects with success flash', function () {
            $response = $this->actingAs($this->user)->put(route('profile.update'), [
                'nickname' => 'Test',
            ]);

            $response->assertRedirect(route('profile.edit'));
            $response->assertSessionHas('success');
        });

        it('logs profile_updated activity', function () {
            $this->actingAs($this->user)->put(route('profile.update'), [
                'nickname' => 'NewNick',
            ]);

            $log = ActivityLog::query()
                ->where('event', 'profile_updated')
                ->where('actor_id', $this->user->id)
                ->first();

            expect($log)->not->toBeNull();
            expect($log->target_username)->toBe($this->user->username);
        });

        it('requires authentication', function () {
            $this->put(route('profile.update'), ['nickname' => 'Test'])
                ->assertRedirectContains(route('login'));
        });

        describe('password change — non-admin', function () {
            it('updates password when current_password is correct', function () {
                $this->actingAs($this->user)->put(route('profile.update'), [
                    'current_password'      => 'currentpass123',
                    'password'              => 'newpassword123',
                    'password_confirmation' => 'newpassword123',
                ]);

                expect(Hash::check('newpassword123', $this->user->fresh()->password))->toBeTrue();
            });

            it('requires current_password when changing password', function () {
                $response = $this->actingAs($this->user)->put(route('profile.update'), [
                    'password'              => 'newpassword123',
                    'password_confirmation' => 'newpassword123',
                ]);

                $response->assertSessionHasErrors('current_password');
                expect(Hash::check('currentpass123', $this->user->fresh()->password))->toBeTrue();
            });

            it('rejects wrong current_password', function () {
                $response = $this->actingAs($this->user)->put(route('profile.update'), [
                    'current_password'      => 'wrongpassword',
                    'password'              => 'newpassword123',
                    'password_confirmation' => 'newpassword123',
                ]);

                $response->assertSessionHasErrors('current_password');
                expect(Hash::check('currentpass123', $this->user->fresh()->password))->toBeTrue();
            });

            it('does not require current_password when only updating nickname', function () {
                $response = $this->actingAs($this->user)->put(route('profile.update'), [
                    'nickname' => 'NoPassNeeded',
                ]);

                $response->assertRedirect(route('profile.edit'));
                expect($this->user->fresh()->nickname)->toBe('NoPassNeeded');
            });
        });

        describe('password change — admin', function () {
            it('can change password without current_password', function () {
                $admin = User::factory()->admin()->create(['password' => Hash::make('adminpass123')]);

                $this->actingAs($admin)->put(route('profile.update'), [
                    'password'              => 'newadminpass123',
                    'password_confirmation' => 'newadminpass123',
                ]);

                expect(Hash::check('newadminpass123', $admin->fresh()->password))->toBeTrue();
            });
        });
    });
});
