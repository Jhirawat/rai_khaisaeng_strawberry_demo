<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $prefixMap = [
            'strawberry-fresh' => 'ST-FRESH',
            'dried-strawberry' => 'ST-DRIED',
            'strawberry-juice' => 'ST-JUICE',
            'strawberry-jam' => 'ST-JAM',
            'strawberry-snacks' => 'ST-SNACK',
            'souvenirs' => 'ST-GIFT',
        ];

        $categories = DB::table('categories')->select('id', 'slug')->get();
        foreach ($categories as $category) {
            $prefix = $prefixMap[$category->slug] ?? strtoupper(substr(preg_replace('/[^A-Za-z0-9]+/', '', (string) $category->slug), 0, 8));
            if ($prefix === '') {
                $prefix = 'PRODUCT';
            }

            $products = DB::table('products')
                ->where('category_id', $category->id)
                ->orderBy('id')
                ->get(['id', 'sku']);

            $running = 1;
            foreach ($products as $product) {
                $currentSku = (string) $product->sku;
                $looksRandom = $currentSku === '' || preg_match('/^[A-F0-9]{8}$/', $currentSku);
                if (!$looksRandom) {
                    continue;
                }

                do {
                    $sku = $prefix.'-'.str_pad((string) $running, 3, '0', STR_PAD_LEFT);
                    $running++;
                    $exists = DB::table('products')->where('sku', $sku)->where('id', '!=', $product->id)->exists();
                } while ($exists);

                DB::table('products')->where('id', $product->id)->update(['sku' => $sku]);
            }
        }
    }

    public function down(): void
    {
        // ไม่ย้อนกลับ SKU เพราะอาจมีการใช้งานในออเดอร์และคลังสินค้าแล้ว
    }
};
