<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryLog extends Model {
    protected $fillable=['inventory_id','type','quantity','note','user_id']; public function inventory(){return $this->belongsTo(Inventory::class);} 
}
