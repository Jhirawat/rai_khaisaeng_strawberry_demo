<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model {
    protected $fillable=['product_id','path','is_primary'];
    protected $casts=['is_primary'=>'boolean'];
    protected $appends=['url','is_seed_image'];
    public function product(){return $this->belongsTo(Product::class);} 


    public function getIsSeedImageAttribute(): bool
    {
        $path = trim((string) $this->path);
        return str_starts_with($path, 'images/products/');
    }

    public function getUrlAttribute(): string
    {
        $path = trim((string) $this->path);
        if ($path === '') {
            return asset('images/products/placeholder.svg');
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }
        if (str_starts_with($path, 'images/')) {
            return asset($path);
        }
        if (str_starts_with($path, 'public/')) {
            return asset('storage/'.substr($path, 7));
        }
        return asset('storage/'.$path);
    }
}
