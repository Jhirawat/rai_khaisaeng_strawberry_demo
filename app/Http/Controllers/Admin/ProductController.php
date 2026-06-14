<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{Product,Category,Inventory,ProductImage};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::orderBy('name')->get();
        $products = Product::with('category','inventory','images')
            ->when($request->filled('search'), function($q) use($request){
                $keyword = trim($request->search);
                $q->where(function($qq) use($keyword){
                    $qq->where('name','like',"%{$keyword}%")
                       ->orWhere('sku','like',"%{$keyword}%")
                       ->orWhere('description','like',"%{$keyword}%");
                });
            })
            ->when($request->filled('category_id'), fn($q)=>$q->where('category_id',$request->category_id))
            ->when($request->filled('status'), fn($q)=>$q->where('status',$request->status))
            ->when($request->stock==='low', fn($q)=>$q->whereHas('inventory', fn($i)=>$i->whereColumn('quantity','<=','low_stock_threshold')->where('quantity','>',0)))
            ->when($request->stock==='out', fn($q)=>$q->whereHas('inventory', fn($i)=>$i->where('quantity','<=',0)))
            ->latest()
            ->paginate((int) $request->get('per_page',20))
            ->withQueryString();
        return view('admin.products.index', compact('products','categories'));
    }

    public function create()
    {
        $categories = Category::all();
        return view('admin.products.form', compact('categories'));
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'category_id'=>'required|exists:categories,id',
            'name'=>'required|string|max:255',
            'name_en'=>'nullable|string|max:255',
            'price'=>'required|numeric|min:0',
            'cost'=>'nullable|numeric|min:0',
            'sku'=>'nullable|string|max:255|unique:products',
            'description'=>'nullable|string',
            'description_en'=>'nullable|string',
            'quantity'=>'required|integer|min:0',
            'low_stock_threshold'=>'nullable|integer|min:0',
            'status'=>'nullable|in:active,inactive',
            'featured'=>'nullable',
            'images.*'=>'mimes:jpg,jpeg,png,webp|max:4096'
        ]);

        $p = Product::create([
            'category_id'=>$d['category_id'],
            'name'=>$d['name'],
            'name_en'=>$d['name_en'] ?? null,
            'slug'=>$this->uniqueSlug($d['name']),
            'description'=>$d['description'] ?? null,
            'description_en'=>$d['description_en'] ?? null,
            'price'=>$d['price'],
            'cost'=>$d['cost'] ?? 0,
            'sku'=>$this->uniqueSku((int) $d['category_id'], $d['sku'] ?? null),
            'status'=>$d['status'] ?? 'active',
            'featured'=>$r->boolean('featured'),
            'added_at'=>now(),
        ]);

        Inventory::create(['product_id'=>$p->id,'quantity'=>$d['quantity'],'low_stock_threshold'=>$d['low_stock_threshold'] ?? 10]);
        $this->storeImages($r, $p);

        return redirect()->route('admin.products.edit', $p)->with('success','บันทึกสินค้าเรียบร้อยแล้ว สามารถแก้ไขข้อมูลและรูปต่อได้ทันที');
    }

    public function edit(Product $product)
    {
        $product->load('inventory','images');
        $categories = Category::all();
        return view('admin.products.form', compact('product','categories'));
    }

    public function update(Request $r, Product $product)
    {
        $d = $r->validate([
            'category_id'=>'required|exists:categories,id',
            'name'=>'required|string|max:255',
            'name_en'=>'nullable|string|max:255',
            'price'=>'required|numeric|min:0',
            'cost'=>'nullable|numeric|min:0',
            'sku'=>'nullable|string|max:255|unique:products,sku,'.$product->id,
            'description'=>'nullable|string',
            'description_en'=>'nullable|string',
            'quantity'=>'nullable|integer|min:0',
            'low_stock_threshold'=>'nullable|integer|min:0',
            'status'=>'nullable|in:active,inactive',
            'featured'=>'nullable',
            'replace_images'=>'nullable',
            'images.*'=>'mimes:jpg,jpeg,png,webp|max:4096'
        ]);

        $product->update([
            'category_id'=>$d['category_id'],
            'name'=>$d['name'],
            'name_en'=>$d['name_en'] ?? null,
            'description'=>$d['description'] ?? null,
            'description_en'=>$d['description_en'] ?? null,
            'price'=>$d['price'],
            'cost'=>$d['cost'] ?? 0,
            'sku'=>$this->uniqueSku((int) $d['category_id'], $d['sku'] ?? null, $product->id),
            'status'=>$d['status'] ?? $product->status,
            'featured'=>$r->boolean('featured'),
        ]);

        $product->inventory()->updateOrCreate(
            ['product_id'=>$product->id],
            [
                'quantity'=>$d['quantity'] ?? ($product->inventory->quantity ?? 0),
                'low_stock_threshold'=>$d['low_stock_threshold'] ?? ($product->inventory->low_stock_threshold ?? 10),
            ]
        );

        if ($r->boolean('replace_images') && $r->hasFile('images')) {
            foreach ($product->images as $oldImage) {
                $this->deleteImageFile($oldImage);
                $oldImage->delete();
            }
        }
        $this->storeImages($r, $product);

        return redirect()->route('admin.products.edit', $product)->with('success','แก้ไขสินค้าเรียบร้อยแล้ว');
    }

    public function quickUpdate(Request $r, Product $product)
    {
        $d=$r->validate([
            'price'=>'nullable|numeric|min:0',
            'quantity'=>'nullable|integer|min:0',
            'status'=>'nullable|in:active,inactive',
        ]);
        if(array_key_exists('price',$d)) $product->update(['price'=>$d['price']]);
        if(array_key_exists('status',$d)) $product->update(['status'=>$d['status']]);
        if(array_key_exists('quantity',$d)) $product->inventory()->updateOrCreate(['product_id'=>$product->id], ['quantity'=>$d['quantity'],'low_stock_threshold'=>$product->inventory->low_stock_threshold ?? 10]);
        return back()->with('success','อัปเดตข้อมูลสินค้าแบบเร็วแล้ว');
    }

    public function bulk(Request $r)
    {
        $d=$r->validate(['ids'=>'required|array','action'=>'required|in:activate,deactivate,delete']);
        $query=Product::whereIn('id',$d['ids']);
        match($d['action']){
            'activate'=>$query->update(['status'=>'active']),
            'deactivate'=>$query->update(['status'=>'inactive']),
            'delete'=>$query->delete(), // ใช้ Soft Delete เพื่อไม่ให้กระทบใบเสร็จ/ออเดอร์เก่า
        };
        return back()->with('success','ดำเนินการกับสินค้าที่เลือกแล้ว');
    }

    public function destroy(Product $product)
    {
        $product->load('images');
        foreach ($product->images as $image) {
            $this->deleteImageFile($image);
        }
        $product->delete();
        return back()->with('success','ลบสินค้าเรียบร้อยแล้ว');
    }

    public function destroyImage(ProductImage $image)
    {
        $product = $image->product;
        $wasPrimary = $image->is_primary;
        $this->deleteImageFile($image);
        $image->delete();
        if ($wasPrimary) {
            $next = $product->images()->first();
            if ($next) $next->update(['is_primary'=>true]);
        }
        return back()->with('success','ลบรูปสินค้าเรียบร้อยแล้ว');
    }

    public function setPrimaryImage(ProductImage $image)
    {
        $image->product->images()->update(['is_primary'=>false]);
        $image->update(['is_primary'=>true]);
        return back()->with('success','ตั้งรูปหลักเรียบร้อยแล้ว');
    }

    private function storeImages(Request $r, Product $product): void
    {
        $files = $r->file('images', []);
        if (empty($files)) {
            return;
        }

        // เมื่ออัปโหลดรูปใหม่ ให้รูปแรกที่อัปโหลดเป็นรูปหลักทันที
        // เพื่อให้หน้า Guest / Member / Admin ดึงรูปจริงจาก Database ไปแสดง ไม่ติดรูปตัวอย่างจาก Seeder
        $product->images()->update(['is_primary' => false]);

        foreach($files as $index => $img){
            ProductImage::create([
                'product_id'=>$product->id,
                'path'=>$img->store('products','public'),
                'is_primary'=>$index === 0,
            ]);
        }
    }

    private function deleteImageFile(ProductImage $image): void
    {
        $path = (string) $image->path;
        if ($path !== '' && !str_starts_with($path,'images/') && !str_starts_with($path,'http')) {
            $path = str_starts_with($path,'storage/') ? substr($path, 8) : $path;
            Storage::disk('public')->delete($path);
        }
    }

    private function uniqueSku(int $categoryId, ?string $manualSku = null, ?int $ignoreProductId = null): string
    {
        $manualSku = strtoupper(trim((string) $manualSku));
        if ($manualSku !== '') {
            $query = Product::where('sku', $manualSku);
            if ($ignoreProductId) $query->where('id','!=',$ignoreProductId);
            if ($query->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['sku' => 'SKU นี้ถูกใช้แล้ว กรุณาเว้นว่างให้ระบบสร้างให้อัตโนมัติ หรือใช้เลขอื่น']);
            }
            return $manualSku;
        }

        $category = Category::find($categoryId);
        $slug = $category?->slug ?? 'product';
        $prefixMap = [
            'strawberry-fresh' => 'ST-FRESH',
            'dried-strawberry' => 'ST-DRIED',
            'strawberry-juice' => 'ST-JUICE',
            'strawberry-jam' => 'ST-JAM',
            'strawberry-snacks' => 'ST-SNACK',
            'souvenirs' => 'ST-GIFT',
        ];
        $prefix = $prefixMap[$slug] ?? strtoupper(substr(preg_replace('/[^A-Za-z0-9]+/', '', $slug), 0, 8));
        if ($prefix === '') {
            $prefix = 'PRODUCT';
        }

        $running = 1;
        do {
            $sku = $prefix.'-'.str_pad((string) $running, 3, '0', STR_PAD_LEFT);
            $query = Product::where('sku', $sku);
            if ($ignoreProductId) {
                $query->where('id', '!=', $ignoreProductId);
            }
            $exists = $query->exists();
            $running++;
        } while ($exists);

        return $sku;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'product-'.strtolower(substr(md5($name),0,8));
        $slug = $base;
        $i = 2;
        while(Product::where('slug',$slug)->exists()) $slug = $base.'-'.$i++;
        return $slug;
    }
}
