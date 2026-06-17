<?php
namespace App\Http\Controllers;

use App\Models\{ThaiProvince,ThaiDistrict,ThaiSubdistrict};
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    public function provinces()
    {
        $rows = ThaiProvince::query()->orderBy('name_th')->get(['id','name_th']);
        if ($rows->isNotEmpty()) return $rows;
        return collect($this->fallbackProvinces())->values()->map(fn($name,$i)=>['id'=>$i+1,'name_th'=>$name]);
    }

    public function districts(ThaiProvince $province)
    {
        $rows = ThaiDistrict::query()->where('province_id', $province->id)->orderBy('name_th')->get(['id','name_th']);
        if ($rows->isNotEmpty()) return $rows;
        return response()->json([['id'=>'fallback-'.$province->id,'name_th'=>'เมือง'.$province->name_th]]);
    }

    public function subdistricts(ThaiDistrict $district)
    {
        $rows = ThaiSubdistrict::query()->where('district_id', $district->id)->orderBy('name_th')->get(['id','name_th','zip_code']);
        if ($rows->isNotEmpty()) return $rows;
        return response()->json([['id'=>'fallback-sub-'.$district->id,'name_th'=>'ในเมือง','zip_code'=>'00000']]);
    }

    private function fallbackProvinces(): array
    {
        return ['กรุงเทพมหานคร','กระบี่','กาญจนบุรี','กาฬสินธุ์','กำแพงเพชร','ขอนแก่น','จันทบุรี','ฉะเชิงเทรา','ชลบุรี','ชัยนาท','ชัยภูมิ','ชุมพร','เชียงราย','เชียงใหม่','ตรัง','ตราด','ตาก','นครนายก','นครปฐม','นครพนม','นครราชสีมา','นครศรีธรรมราช','นครสวรรค์','นนทบุรี','นราธิวาส','น่าน','บึงกาฬ','บุรีรัมย์','ปทุมธานี','ประจวบคีรีขันธ์','ปราจีนบุรี','ปัตตานี','พระนครศรีอยุธยา','พะเยา','พังงา','พัทลุง','พิจิตร','พิษณุโลก','เพชรบุรี','เพชรบูรณ์','แพร่','ภูเก็ต','มหาสารคาม','มุกดาหาร','แม่ฮ่องสอน','ยโสธร','ยะลา','ร้อยเอ็ด','ระนอง','ระยอง','ราชบุรี','ลพบุรี','ลำปาง','ลำพูน','เลย','ศรีสะเกษ','สกลนคร','สงขลา','สตูล','สมุทรปราการ','สมุทรสงคราม','สมุทรสาคร','สระแก้ว','สระบุรี','สิงห์บุรี','สุโขทัย','สุพรรณบุรี','สุราษฎร์ธานี','สุรินทร์','หนองคาย','หนองบัวลำภู','อ่างทอง','อำนาจเจริญ','อุดรธานี','อุตรดิตถ์','อุทัยธานี','อุบลราชธานี'];
    }
}
