<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Inventory extends Model {
    protected $fillable=['product_id','quantity','low_stock_threshold']; public function product(){return $this->belongsTo(Product::class);} public function logs(){return $this->hasMany(InventoryLog::class);} 
}
