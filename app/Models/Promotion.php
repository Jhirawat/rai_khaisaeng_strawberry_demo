<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Promotion extends Model
{
    protected $fillable = ['title','subtitle','description','button_text','button_url','image_path','position','is_active','starts_at','ends_at','sort_order'];
    protected $casts = ['is_active'=>'boolean','starts_at'=>'datetime','ends_at'=>'datetime'];

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) return null;
        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) return $this->image_path;
        return Storage::disk('public')->exists($this->image_path) ? Storage::url($this->image_path) : asset($this->image_path);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true)
            ->where(function($x){ $x->whereNull('starts_at')->orWhere('starts_at','<=',now()); })
            ->where(function($x){ $x->whereNull('ends_at')->orWhere('ends_at','>=',now()); });
    }
}
