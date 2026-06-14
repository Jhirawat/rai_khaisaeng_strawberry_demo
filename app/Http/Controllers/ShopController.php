<?php
namespace App\Http\Controllers;
use App\Models\{Product,Category,Review}; use Illuminate\Http\Request;
class ShopController extends Controller{
 public function home(){ $featured=Product::with('category','inventory','images')->where('featured',1)->take(8)->get(); $categories=Category::where('is_active',1)->get(); return view('shop.home',compact('featured','categories')); }
 public function products(Request $r){ $q=Product::with('category','inventory','images')->where('status','active'); if($r->filled('category')) $q->whereHas('category',fn($c)=>$c->where('slug',$r->category)); if($r->filled('search')) { $keyword='%'.$r->search.'%'; $q->where(function($qq) use($keyword){ $qq->where('name','like',$keyword)->orWhere('name_en','like',$keyword)->orWhere('sku','like',$keyword)->orWhere('description','like',$keyword)->orWhere('description_en','like',$keyword); }); } $products=$q->latest()->paginate(12)->withQueryString(); $categories=Category::where('is_active',1)->get(); return view('shop.products',compact('products','categories')); }
 public function show(Product $product){$product->load('category','images','inventory','reviews.user'); return view('shop.product',compact('product'));}
}
