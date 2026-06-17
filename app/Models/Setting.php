<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Setting extends Model
{
    protected $fillable = ['key','value'];
    public $timestamps = true;

    public static function getValue(string $key, ?string $default = null): ?string
    {
        return optional(static::where('key', $key)->first())->value ?? $default;
    }

    public static function setValue(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
