<?php

declare(strict_types=1);

use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Validator;

describe('LoginRequest', function () {
    describe('authorize', function () {
        it('returns true for all users', function () {
            $request = new LoginRequest();

            expect($request->authorize())->toBeTrue();
        });
    });

    describe('validation rules', function () {
        it('passes with valid credentials', function () {
            $request = new LoginRequest();
            $rules = $request->rules();

            $validator = Validator::make([
                'username' => 'validuser',
                'password' => 'password123',
                'remember' => true,
            ], $rules);

            expect($validator->passes())->toBeTrue();
        });

        it('fails when username is missing', function () {
            $request = new LoginRequest();
            $rules = $request->rules();

            $validator = Validator::make([
                'password' => 'password123',
            ], $rules);

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('username'))->toBeTrue();
        });

        it('fails when password is missing', function () {
            $request = new LoginRequest();
            $rules = $request->rules();

            $validator = Validator::make([
                'username' => 'validuser',
            ], $rules);

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('password'))->toBeTrue();
        });

        it('fails when username exceeds 255 characters', function () {
            $request = new LoginRequest();
            $rules = $request->rules();

            $validator = Validator::make([
                'username' => str_repeat('a', 256),
                'password' => 'password123',
            ], $rules);

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('username'))->toBeTrue();
        });

        it('fails when password is less than 8 characters', function () {
            $request = new LoginRequest();
            $rules = $request->rules();

            $validator = Validator::make([
                'username' => 'validuser',
                'password' => '1234567',
            ], $rules);

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('password'))->toBeTrue();
        });

        it('passes with password of exactly 8 characters', function () {
            $request = new LoginRequest();
            $rules = $request->rules();

            $validator = Validator::make([
                'username' => 'validuser',
                'password' => '12345678',
            ], $rules);

            expect($validator->passes())->toBeTrue();
        });

        it('accepts remember as boolean', function () {
            $request = new LoginRequest();
            $rules = $request->rules();

            $validatorTrue = Validator::make([
                'username' => 'validuser',
                'password' => 'password123',
                'remember' => true,
            ], $rules);

            $validatorFalse = Validator::make([
                'username' => 'validuser',
                'password' => 'password123',
                'remember' => false,
            ], $rules);

            expect($validatorTrue->passes())->toBeTrue();
            expect($validatorFalse->passes())->toBeTrue();
        });

        it('allows remember to be omitted', function () {
            $request = new LoginRequest();
            $rules = $request->rules();

            $validator = Validator::make([
                'username' => 'validuser',
                'password' => 'password123',
            ], $rules);

            expect($validator->passes())->toBeTrue();
        });
    });

    describe('custom messages', function () {
        it('returns custom message for required username', function () {
            $request = new LoginRequest();
            $messages = $request->messages();

            expect($messages['username.required'])->toBe('O campo Usuário não pode estar vázio.');
        });

        it('returns custom message for username max length', function () {
            $request = new LoginRequest();
            $messages = $request->messages();

            expect($messages['username.max'])->toBe('O campo usuário deve ter menos de 255 caracteres.');
        });

        it('returns custom message for required password', function () {
            $request = new LoginRequest();
            $messages = $request->messages();

            expect($messages['password.required'])->toBe('O campo da senha não pode estar vázio.');
        });

        it('returns custom message for password min length', function () {
            $request = new LoginRequest();
            $messages = $request->messages();

            expect($messages['password.min'])->toBe('O campo da senha não pode ter menos de 8 caracteres.');
        });
    });
});
