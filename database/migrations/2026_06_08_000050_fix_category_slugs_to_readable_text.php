<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $map = [
            'สตรอว์เบอร์รีสด' => 'strawberry-fresh',
            'สตรอว์เบอร์รี่สด' => 'strawberry-fresh',
            'สตรอว์เบอร์รีอบแห้ง' => 'dried-strawberry',
            'น้ำสตรอว์เบอร์รี' => 'strawberry-juice',
            'น้ำสตรอว์เบอร์รี่' => 'strawberry-juice',
            'แยมสตรอว์เบอร์รี' => 'strawberry-jam',
            'แยมสตรอว์เบอร์รี่' => 'strawberry-jam',
            'ขนมแปรรูปจากสตรอว์เบอร์รี' => 'strawberry-snacks',
            'ขนมแปรรูปจากสตรอว์เบอร์รี่' => 'strawberry-snacks',
            'ผลิตภัณฑ์แปรรูป' => 'processed-products',
            'ของฝากและของที่ระลึก' => 'souvenirs',
            'น้ำ' => 'drinks',
        ];
        foreach ($map as $name => $slug) {
            $category = DB::table('categories')->where('name',$name)->first();
            if ($category) {
                $final = $slug;
                $i = 2;
                while (DB::table('categories')->where('slug',$final)->where('id','!=',$category->id)->exists()) {
                    $final = $slug.'-'.$i++;
                }
                DB::table('categories')->where('id',$category->id)->update(['slug'=>$final]);
            }
        }
    }
    public function down(): void {}
};
