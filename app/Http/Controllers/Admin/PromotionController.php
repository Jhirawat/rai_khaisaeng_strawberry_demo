<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Support\ActivityLogger;

class PromotionController extends Controller
{
    public function index()
    {
        $promotions = Promotion::query()->orderBy('position')->orderBy('sort_order')->latest()->paginate(20);
        return view('admin.promotions.index', compact('promotions'));
    }
    public function create(){ return view('admin.promotions.form', ['promotion'=>new Promotion()]); }
    public function store(Request $request)
    {
        $data = $this->validated($request);
        if ($request->hasFile('image')) $data['image_path'] = $request->file('image')->store('promotions','public');
        $promotion = Promotion::create($data);
        ActivityLogger::log('promotion.created', $promotion, ['position'=>$promotion->position]);
        return redirect()->route('admin.promotions.index')->with('success','เพิ่มโฆษณา/โปรโมชั่นเรียบร้อยแล้ว');
    }
    public function edit(Promotion $promotion){ return view('admin.promotions.form', compact('promotion')); }
    public function update(Request $request, Promotion $promotion)
    {
        $data = $this->validated($request);
        if ($request->boolean('remove_image') && $promotion->image_path) {
            Storage::disk('public')->delete($promotion->image_path);
            $data['image_path'] = null;
        }
        if ($request->hasFile('image')) {
            if ($promotion->image_path) Storage::disk('public')->delete($promotion->image_path);
            $data['image_path'] = $request->file('image')->store('promotions','public');
        }
        $promotion->update($data);
        ActivityLogger::log('promotion.updated', $promotion, ['position'=>$promotion->position]);
        return redirect()->route('admin.promotions.index')->with('success','บันทึกโปรโมชั่นเรียบร้อยแล้ว');
    }
    public function destroy(Promotion $promotion)
    {
        if ($promotion->image_path) Storage::disk('public')->delete($promotion->image_path);
        $label = $promotion->title;
        $promotion->delete();
        ActivityLogger::log('promotion.deleted', $promotion, [], $label);
        return back()->with('success','ลบโปรโมชั่นเรียบร้อยแล้ว');
    }
    private function validated(Request $request): array
    {
        return $request->validate([
            'title'=>'required|string|max:255','subtitle'=>'nullable|string|max:255','description'=>'nullable|string|max:2000',
            'button_text'=>'nullable|string|max:100','button_url'=>'nullable|string|max:255','position'=>'required|string|max:80',
            'sort_order'=>'nullable|integer|min:0','is_active'=>'nullable|boolean','starts_at'=>'nullable|date','ends_at'=>'nullable|date|after_or_equal:starts_at',
            'image'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:4096','remove_image'=>'nullable|boolean'
        ]) + ['is_active'=>$request->boolean('is_active'), 'sort_order'=>(int)$request->input('sort_order',0)];
    }
}
