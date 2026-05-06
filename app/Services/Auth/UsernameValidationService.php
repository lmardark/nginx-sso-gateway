<?php

declare(strict_types=1);

namespace App\Services\Auth;

use Closure;

final class UsernameValidationService
{
    /** @return list<Closure> */
    public static function buildRules(string $type, string $customPattern): array
    {
        if ($type === 'cpf') {
            return [static function (string $attribute, mixed $value, Closure $fail): void {
                $cpf = preg_replace('/[^0-9]/', '', (string) $value) ?? '';

                if (mb_strlen($cpf) !== 11 || (bool) preg_match('/^(\d)\1{10}$/', $cpf)) {
                    $fail('CPF inválido.');

                    return;
                }

                for ($t = 9; $t < 11; $t++) {
                    $sum = 0;

                    for ($i = 0; $i < $t; $i++) {
                        $sum += (int) $cpf[$i] * ($t + 1 - $i);
                    }

                    $digit = (10 * $sum % 11) % 10;

                    if ((int) $cpf[$t] !== $digit) {
                        $fail('CPF inválido.');

                        return;
                    }
                }
            }];
        }

        if ($type === 'celular') {
            return [static function (string $attribute, mixed $value, Closure $fail): void {
                if (! preg_match('/^\(?\d{2}\)?\s?9\d{4}[\s-]?\d{4}$/', (string) $value)) {
                    $fail('Número de celular inválido. Ex: (00) 90000-0000');
                }
            }];
        }

        if ($type === 'personalizado' && $customPattern !== '') {
            return [static function (string $attribute, mixed $value, Closure $fail) use ($customPattern): void {
                if (@preg_match('~'.$customPattern.'~', (string) $value) !== 1) {
                    $fail('O usuário não corresponde ao padrão personalizado.');
                }
            }];
        }

        return [];
    }
}
