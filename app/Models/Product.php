<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model {
    use SoftDeletes;
    protected $fillable=['category_id','name','name_en','slug','description','description_en','price','cost','sku','status','featured','added_at'];
    protected $casts=['featured'=>'boolean','added_at'=>'datetime','price'=>'decimal:2'];

    public function category(){return $this->belongsTo(Category::class);} 
    public function images(){return $this->hasMany(ProductImage::class);} 
    public function inventory(){return $this->hasOne(Inventory::class);} 
    public function orderItems(){return $this->hasMany(OrderItem::class);} 
    public function reviews(){return $this->hasMany(Review::class);} 

    public function getPrimaryImageAttribute()
    {
        $images = $this->relationLoaded('images') ? $this->images : $this->images()->get();

        // ใช้รูปที่อัปโหลดจริงจาก Database ก่อนเสมอ
        // path แบบ products/xxx.jpg คือรูปที่แอดมินอัปโหลดเข้า storage/app/public/products
        // ส่วน images/products/*.svg คือรูปตัวอย่างจาก Seeder เท่านั้น
        $uploadedImages = $images->filter(fn ($image) => !$image->is_seed_image);
        if ($uploadedImages->isNotEmpty()) {
            return $uploadedImages->firstWhere('is_primary', true) ?: $uploadedImages->first();
        }

        return $images->firstWhere('is_primary', true) ?: $images->first();
    }

    public function getPrimaryImageUrlAttribute(): string
    {
        return $this->primary_image?->url ?? asset('images/products/placeholder.svg');
    }

    public function getDisplayNameAttribute(): string
    {
        return app()->getLocale() === 'en' && !empty($this->name_en) ? $this->name_en : $this->name;
    }

    public function getDisplayDescriptionAttribute(): ?string
    {
        return app()->getLocale() === 'en' && !empty($this->description_en) ? $this->description_en : $this->description;
    }
}
