<?php

namespace App\Support;

use App\Models\{ThaiProvince, ThaiDistrict, ThaiSubdistrict};
use Illuminate\Validation\ValidationException;

trait ValidatesThaiAddress
{
    protected function validateThaiAddressOrFail(array $data, bool $requireSubdistrict = true): array
    {
        $provinceName = trim((string)($data['province'] ?? ''));
        $districtName = trim((string)($data['district'] ?? ''));
        $subdistrictName = trim((string)($data['subdistrict'] ?? ''));
        $postalCode = trim((string)($data['postal_code'] ?? ''));

        $province = ThaiProvince::query()
            ->where('name_th', $provinceName)
            ->orWhere('name_en', $provinceName)
            ->first();

        if (!$province) {
            throw ValidationException::withMessages([
                'province' => 'จังหวัดไม่ถูกต้อง กรุณาเลือกจังหวัดจากรายการในระบบ',
            ]);
        }

        $district = ThaiDistrict::query()
            ->where('province_id', $province->id)
            ->where(function ($q) use ($districtName) {
                $q->where('name_th', $districtName)->orWhere('name_en', $districtName);
            })
            ->first();

        if (!$district) {
            throw ValidationException::withMessages([
                'district' => 'อำเภอ/เขตไม่ตรงกับจังหวัดที่เลือก กรุณาเลือกจากรายการในระบบ',
            ]);
        }

        $subdistrict = null;
        if ($requireSubdistrict || $subdistrictName !== '') {
            $subdistrict = ThaiSubdistrict::query()
                ->where('district_id', $district->id)
                ->where(function ($q) use ($subdistrictName) {
                    $q->where('name_th', $subdistrictName)->orWhere('name_en', $subdistrictName);
                })
                ->first();

            if (!$subdistrict) {
                throw ValidationException::withMessages([
                    'subdistrict' => 'ตำบล/แขวงไม่ตรงกับอำเภอที่เลือก กรุณาเลือกจากรายการในระบบ',
                ]);
            }

            if ($subdistrict->zip_code && $postalCode !== (string) $subdistrict->zip_code) {
                throw ValidationException::withMessages([
                    'postal_code' => 'รหัสไปรษณีย์ไม่ตรงกับตำบล/แขวงที่เลือก',
                ]);
            }
        }

        $data['province'] = $province->name_th;
        $data['district'] = $district->name_th;
        $data['subdistrict'] = $subdistrict ? $subdistrict->name_th : ($data['subdistrict'] ?? null);
        $data['postal_code'] = $subdistrict && $subdistrict->zip_code ? (string) $subdistrict->zip_code : $postalCode;

        return $data;
    }
}
