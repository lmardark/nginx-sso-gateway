<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $key
 * @property string|null $value
 */
final class Setting extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        return self::find($key)?->value ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /** @return array{app_name: string, show_logo: bool, primary_color: string, custom_css: string} */
    public static function loginSettings(): array
    {
        $raw = self::whereIn('key', [
            'login_app_name',
            'login_show_logo',
            'login_primary_color',
            'login_custom_css',
        ])->pluck('value', 'key');

        return [
            'app_name' => $raw->get('login_app_name') ?? 'Sistema de Autenticação',
            'show_logo' => ($raw->get('login_show_logo') ?? '1') !== '0',
            'primary_color' => $raw->get('login_primary_color') ?? '#F53003',
            'custom_css' => $raw->get('login_custom_css') ?? '',
        ];
    }
}
