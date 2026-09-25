<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\ActivityLogger;
use App\Support\ForeignKeyConstraintViolation;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        return view('admin.categories.index', [
            'categories' => Category::withCount([
                'products' => fn ($query) => $query->withTrashed(),
            ])->paginate(20),
        ]);
    }

    public function store(Request $r)
    {
        $d = $r->validate(['name' => 'required', 'name_en' => 'nullable|string|max:255', 'description' => 'nullable']);
        $category = Category::create($d + ['slug' => $this->makeSlug($d['name'])]);
        ActivityLogger::log('category.created', $category, ['slug' => $category->slug]);

        return back()->with('success', 'เพิ่มหมวดหมู่เรียบร้อยแล้ว');
    }

    public function update(Request $r, Category $category)
    {
        $d = $r->validate(['name' => 'required', 'name_en' => 'nullable|string|max:255', 'description' => 'nullable', 'is_active' => 'nullable']);
        $d['slug'] = $this->makeSlug($d['name'], $category->id);
        $category->update($d);
        ActivityLogger::log('category.updated', $category, ['slug' => $category->slug]);

        return back()->with('success', 'อัปเดตหมวดหมู่เรียบร้อยแล้ว');
    }

    public function destroy(Category $category)
    {
        try {
            $deleted = DB::transaction(function () use ($category): bool {
                $lockedCategory = Category::query()->lockForUpdate()->findOrFail($category->id);

                if ($lockedCategory->products()->withTrashed()->exists()) {
                    return false;
                }

                $label = $lockedCategory->name;
                $lockedCategory->delete();
                ActivityLogger::log('category.deleted', $lockedCategory, [], $label);

                return true;
            });
        } catch (QueryException $exception) {
            if (! ForeignKeyConstraintViolation::causedBy($exception)) {
                throw $exception;
            }

            return back()->with('error', 'ไม่สามารถลบหมวดหมู่ได้ เนื่องจากมีข้อมูลที่เกี่ยวข้อง กรุณาย้ายสินค้าหรือปิดใช้งานหมวดหมู่แทน');
        }

        if (! $deleted) {
            return back()->with('error', 'ไม่สามารถลบหมวดหมู่ที่ยังมีสินค้าได้ กรุณาย้ายสินค้าหรือปิดใช้งานหมวดหมู่แทน');
        }

        return back()->with('success', 'ลบหมวดหมู่เรียบร้อยแล้ว');
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
        if (! $base) {
            $base = 'category-'.strtolower(substr(md5($name), 0, 6));
        }
        $slug = $base;
        $i = 2;
        while (Category::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
