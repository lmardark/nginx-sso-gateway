<?php

declare(strict_types=1);

use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->authService = new AuthService();
});

describe('AuthService', function () {
    describe('autenticationFunction', function () {
        it('returns true when credentials are valid', function () {
            Auth::shouldReceive('attempt')
                ->once()
                ->with([
                    'username' => 'validuser',
                    'password' => 'validpassword',
                ], false)
                ->andReturn(true);

            $result = $this->authService->autenticationFunction([
                'username' => 'validuser',
                'password' => 'validpassword',
            ]);

            expect($result)->toBeTrue();
        });

        it('returns false when credentials are invalid', function () {
            Auth::shouldReceive('attempt')
                ->once()
                ->with([
                    'username' => 'invaliduser',
                    'password' => 'wrongpassword',
                ], false)
                ->andReturn(false);

            $result = $this->authService->autenticationFunction([
                'username' => 'invaliduser',
                'password' => 'wrongpassword',
            ]);

            expect($result)->toBeFalse();
        });

        it('passes remember option when provided as true', function () {
            Auth::shouldReceive('attempt')
                ->once()
                ->with([
                    'username' => 'user',
                    'password' => 'password',
                ], true)
                ->andReturn(true);

            $result = $this->authService->autenticationFunction([
                'username' => 'user',
                'password' => 'password',
                'remember' => true,
            ]);

            expect($result)->toBeTrue();
        });

        it('defaults remember to false when not provided', function () {
            Auth::shouldReceive('attempt')
                ->once()
                ->with([
                    'username' => 'user',
                    'password' => 'password',
                ], false)
                ->andReturn(true);

            $result = $this->authService->autenticationFunction([
                'username' => 'user',
                'password' => 'password',
            ]);

            expect($result)->toBeTrue();
        });
    });
});
