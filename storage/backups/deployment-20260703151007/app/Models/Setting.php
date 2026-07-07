<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    private static ?bool $settingsTableExists = null;

    /**
     * @var array<string, string|null>
     */
    private static array $valueCache = [];

    protected $fillable = [
        'key',
        'value',
    ];

    public static function valueFor(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, self::$valueCache)) {
            return self::$valueCache[$key] ?? $default;
        }

        if (! self::hasSettingsTable()) {
            return $default;
        }

        $value = static::query()->where('key', $key)->value('value') ?? $default;
        self::$valueCache[$key] = $value;

        return $value;
    }

    public static function setValue(string $key, ?string $value): void
    {
        if (! self::hasSettingsTable()) {
            return;
        }

        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        self::$valueCache[$key] = $value;
    }

    private static function hasSettingsTable(): bool
    {
        if (self::$settingsTableExists === true) {
            return true;
        }

        self::$settingsTableExists = Schema::hasTable('settings');

        return self::$settingsTableExists;
    }
}
