<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ThaiSubdistrict extends Model
{
    protected $fillable=['district_id','code','name_th','name_en','zip_code'];
    public function district(){ return $this->belongsTo(ThaiDistrict::class,'district_id'); }
}
