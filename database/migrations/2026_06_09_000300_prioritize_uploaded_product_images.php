<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_images')) {
            return;
        }

        $productIds = DB::table('product_images')
            ->select('product_id')
            ->where(function ($query) {
                $query->where('path', 'like', 'products/%')
                    ->orWhere('path', 'like', 'storage/products/%')
                    ->orWhere('path', 'like', 'public/products/%');
            })
            ->distinct()
            ->pluck('product_id');

        foreach ($productIds as $productId) {
            DB::table('product_images')->where('product_id', $productId)->update(['is_primary' => false]);

            $uploadedImage = DB::table('product_images')
                ->where('product_id', $productId)
                ->where(function ($query) {
                    $query->where('path', 'like', 'products/%')
                        ->orWhere('path', 'like', 'storage/products/%')
                        ->orWhere('path', 'like', 'public/products/%');
                })
                ->orderBy('id')
                ->first();

            if ($uploadedImage) {
                DB::table('product_images')->where('id', $uploadedImage->id)->update(['is_primary' => true]);
            }
        }
    }

    public function down(): void
    {
        // ไม่ย้อนกลับ เพราะเป็นการจัดลำดับรูปหลักเพื่อแก้การแสดงผลเท่านั้น
    }
};
