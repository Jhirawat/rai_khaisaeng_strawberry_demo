<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Category extends Model {
    protected $fillable=['name','name_en','slug','description','description_en','is_active'];
    protected $casts=['is_active'=>'boolean'];
    public function products(){return $this->hasMany(Product::class);} 

    public function getDisplayNameAttribute(): string
    {
        return app()->getLocale() === 'en' && !empty($this->name_en) ? $this->name_en : $this->name;
    }

    public function getDisplayDescriptionAttribute(): ?string
    {
        return app()->getLocale() === 'en' && !empty($this->description_en) ? $this->description_en : $this->description;
    }
}
