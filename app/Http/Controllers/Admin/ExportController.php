<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{Product,Order,User,Inventory};
class ExportController extends Controller
{
    public function index(){ return view('admin.exports.index'); }
    public function products(){
        return $this->csv('products_export_'.now()->format('Ymd_His').'.csv', ['SKU','ชื่อสินค้า','หมวดหมู่','ราคา','สถานะ','คงเหลือ'], Product::with('category','inventory')->get()->map(fn($p)=>[$p->sku,$p->name,$p->category->name ?? '',$p->price,$p->status,$p->inventory->quantity ?? 0]));
    }
    public function orders(){
        return $this->csv('orders_export_'.now()->format('Ymd_His').'.csv', ['เลขออเดอร์','ลูกค้า','อีเมล','สถานะ','ยอดรวม','วันที่'], Order::with('user')->latest()->get()->map(fn($o)=>[$o->order_number ?? $o->id,$o->user->name ?? '',$o->user->email ?? '',$o->status,$o->total_amount ?? $o->total ?? '',optional($o->created_at)->format('Y-m-d H:i')]));
    }
    public function users(){
        return $this->csv('users_export_'.now()->format('Ymd_His').'.csv', ['ชื่อ','อีเมล','เบอร์','สิทธิ์','สถานะ','วันที่สมัคร'], User::latest()->get()->map(fn($u)=>[$u->name,$u->email,$u->phone,$u->role,($u->is_active ?? true) ? 'active':'inactive',optional($u->created_at)->format('Y-m-d H:i')]));
    }
    public function inventory(){
        return $this->csv('inventory_export_'.now()->format('Ymd_His').'.csv', ['SKU','สินค้า','คงเหลือ','จุดเตือนใกล้หมด'], Inventory::with('product')->get()->map(fn($i)=>[$i->product->sku ?? '',$i->product->name ?? '',$i->quantity,$i->low_stock_threshold]));
    }
    private function csv(string $filename, array $headings, $rows){
        $callback = function() use($headings,$rows){
            $out = fopen('php://output','w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headings);
            foreach($rows as $row) fputcsv($out, $row);
            fclose($out);
        };
        return response()->streamDownload($callback, $filename, ['Content-Type'=>'text/csv; charset=UTF-8']);
    }
}
