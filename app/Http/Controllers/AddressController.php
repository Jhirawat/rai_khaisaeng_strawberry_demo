<?php
namespace App\Http\Controllers;

use App\Models\{ThaiProvince,ThaiDistrict,ThaiSubdistrict};

class AddressController extends Controller
{
    public function provinces()
    {
        return ThaiProvince::query()->orderBy('name_th')->get(['id','name_th']);
    }

    public function districts(ThaiProvince $province)
    {
        return ThaiDistrict::query()->where('province_id', $province->id)->orderBy('name_th')->get(['id','name_th']);
    }

    public function subdistricts(ThaiDistrict $district)
    {
        return ThaiSubdistrict::query()->where('district_id', $district->id)->orderBy('name_th')->get(['id','name_th','zip_code']);
    }
}
