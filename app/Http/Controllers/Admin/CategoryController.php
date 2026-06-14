<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        return view('admin.categories.index', ['categories' => Category::withCount('products')->paginate(20)]);
    }

    public function store(Request $r)
    {
        $d = $r->validate(['name' => 'required', 'name_en' => 'nullable|string|max:255', 'description' => 'nullable']);
        Category::create($d + ['slug' => $this->makeSlug($d['name'])]);
        return back()->with('success','เพิ่มหมวดหมู่เรียบร้อยแล้ว');
    }

    public function update(Request $r, Category $category)
    {
        $d = $r->validate(['name' => 'required', 'name_en' => 'nullable|string|max:255', 'description' => 'nullable', 'is_active' => 'nullable']);
        $d['slug'] = $this->makeSlug($d['name'], $category->id);
        $category->update($d);
        return back()->with('success','อัปเดตหมวดหมู่เรียบร้อยแล้ว');
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return back()->with('success','ลบหมวดหมู่เรียบร้อยแล้ว');
    }

    private function makeSlug(string $name, ?int $ignoreId = null): string
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
        $base = $map[$name] ?? Str::slug($name);
        if (!$base) {
            $base = 'category-' . strtolower(substr(md5($name), 0, 6));
        }
        $slug = $base;
        $i = 2;
        while (Category::where('slug', $slug)->when($ignoreId, fn($q) => $q->where('id','!=',$ignoreId))->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
