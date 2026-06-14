<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ShippingAddress extends Model {
    protected $fillable=['user_id','recipient_name','phone','address','province','district','subdistrict','postal_code','is_default']; protected $casts=['is_default'=>'boolean']; public function user(){return $this->belongsTo(User::class);} 
}
