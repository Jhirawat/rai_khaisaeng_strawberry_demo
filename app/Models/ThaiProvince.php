<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ThaiProvince extends Model
{
    protected $fillable=['code','name_th','name_en'];
    public function districts(){ return $this->hasMany(ThaiDistrict::class,'province_id'); }
}
