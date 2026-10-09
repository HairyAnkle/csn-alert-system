<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Setting extends Model
{
    use HasFactory, LogsActivity;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'value',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['key', 'value'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * Get default system settings.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'sound_on_high' => true,
            'sound_on_low' => true,
            'hotline_pdrrmo' => '(02) 8820-7377',
            'hotline_ambulance' => '911 / (02) 8825-1022',
        ];
    }

    /**
     * Get a setting by key, or return fallback default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        if ($setting === null) {
            $defaults = static::defaults();

            return $default ?? ($defaults[$key] ?? null);
        }

        return static::castValue($key, $setting->value);
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, mixed $value): static
    {
        $stringValue = is_bool($value) ? ($value ? '1' : '0') : (string) $value;

        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $stringValue]
        );
    }

    /**
     * Get all settings merged with defaults and properly type-cast.
     *
     * @return array<string, mixed>
     */
    public static function allValues(): array
    {
        $merged = static::defaults();
        $stored = static::pluck('value', 'key')->all();

        foreach ($stored as $k => $v) {
            $merged[$k] = static::castValue($k, $v);
        }

        return $merged;
    }

    /**
     * Type-cast specific setting keys.
     */
    protected static function castValue(string $key, mixed $value): mixed
    {
        if (in_array($key, ['sound_on_high', 'sound_on_low'], true)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        return $value;
    }
}
