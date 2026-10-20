<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    /**
     * Ambil satu nilai setting, dengan fallback default.
     * Di-cache supaya tidak query berulang kali tiap request.
     */
    public static function get(string $key, $default = null)
    {
        $settings = Cache::rememberForever('settings_all', function () {
            return static::all()->pluck('value', 'key');
        });

        return $settings[$key] ?? $default;
    }

    /**
     * Set satu nilai setting, lalu hapus cache supaya ter-refresh.
     */
    public static function set(string $key, $value, string $group = 'general'): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
        Cache::forget('settings_all');
    }

    /**
     * Ambil semua setting dalam satu grup, sebagai array key => value.
     */
    public static function getGroup(string $group): array
    {
        return static::where('group', $group)->pluck('value', 'key')->toArray();
    }
}