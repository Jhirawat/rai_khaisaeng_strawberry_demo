<?php
namespace App\Http\Controllers\Member;
use App\Http\Controllers\Controller; use App\Models\{Cart,CartItem,Product}; use Illuminate\Http\Request;
class CartController extends Controller{
 private function cart(){return Cart::firstOrCreate(['user_id'=>auth()->id()]);}
 public function index(){ $cart=$this->cart()->load('items.product.images','items.product.inventory'); return view('member.cart',compact('cart')); }
 public function add(Request $r, Product $product){
  $r->validate(['quantity'=>'required|integer|min:1|max:999']);
  if($product->status !== 'active'){
   $msg='สินค้านี้ยังไม่พร้อมขาย';
   if($r->expectsJson() || $r->ajax()) return response()->json(['success'=>false,'message'=>$msg],422);
   return back()->with('error',$msg);
  }
  $available=(int) optional($product->inventory)->quantity;
  $cart=$this->cart();
  $item=CartItem::firstOrNew(['cart_id'=>$cart->id,'product_id'=>$product->id]);
  $newQty=($item->exists?$item->quantity:0)+(int)$r->quantity;
  if($available < $newQty){
   $msg='สต๊อกสินค้าไม่พอ คงเหลือ '.$available.' ชิ้น';
   if($r->expectsJson() || $r->ajax()) return response()->json(['success'=>false,'message'=>$msg,'available'=>$available],422);
   return back()->with('error',$msg);
  }
  $item->quantity=$newQty;
  $item->price=$product->price;
  $item->save();
  $count=$cart->items()->sum('quantity');
  if($r->expectsJson() || $r->ajax()) return response()->json(['success'=>true,'message'=>'เพิ่มสินค้าลงตะกร้าแล้ว','cart_count'=>$count]);
  return back()->with('success','เพิ่มสินค้าลงตะกร้าแล้ว');
 }
 public function update(Request $r, CartItem $item){
  abort_unless($item->cart->user_id===auth()->id(),403);
  $r->validate(['quantity'=>'required|integer|min:1|max:999']);
  $item->load('product.inventory');
  $available=(int) optional($item->product->inventory)->quantity;
  if($available < (int)$r->quantity) return back()->with('error','สต๊อกสินค้าไม่พอ คงเหลือ '.$available.' ชิ้น');
  $item->update(['quantity'=>(int)$r->quantity]);
  return back()->with('success','อัปเดตจำนวนสินค้าแล้ว');
 }
 public function destroy(CartItem $item){abort_unless($item->cart->user_id===auth()->id(),403); $item->delete(); return back();}
}
