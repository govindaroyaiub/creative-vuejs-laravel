<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchedulerSetting extends Model
{
    protected $fillable = ['key', 'value', 'description'];

    public static function getValue($key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function setValue($key, $value, $description = null)
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'description' => $description]
        );
    }

    public static function getCacheCleanupTime()
    {
        return static::getValue('cache_cleanup_time', '04:30');
    }

    public static function isCacheCleanupEnabled()
    {
        return static::getValue('cache_cleanup_enabled', 'true') === 'true';
    }

    public static function getTimezone()
    {
        return static::getValue('timezone', 'Asia/Dhaka');
    }

    public static function setTimezone($timezone)
    {
        return static::setValue('timezone', $timezone, 'Application timezone for scheduler');
    }
}
