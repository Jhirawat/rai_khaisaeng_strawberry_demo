<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
class User extends Authenticatable {
    use SoftDeletes;
    protected $fillable=['name','email','password','phone','address','role','is_active','provider','provider_id','avatar']; protected $hidden=['password','remember_token']; protected $casts=['email_verified_at'=>'datetime','password'=>'hashed','is_active'=>'boolean']; public function orders(){return $this->hasMany(Order::class);} public function cart(){return $this->hasOne(Cart::class);} public function addresses(){return $this->hasMany(ShippingAddress::class);} public function reviews(){return $this->hasMany(Review::class);} 
}
