<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Order,User,Product,Inventory,Category,Payment,OrderItem};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $year = (int)($request->get('year', date('Y')));
        $previousYear = $year - 1;
        $validStatuses = ['paid','preparing','packed','shipped','delivered'];

        $totalSales = Order::whereIn('status',$validStatuses)->sum('total');
        $todaySales = Order::whereIn('status',$validStatuses)->whereDate('created_at', today())->sum('total');
        $monthSales = Order::whereIn('status',$validStatuses)->whereYear('created_at',date('Y'))->whereMonth('created_at',date('m'))->sum('total');
        $totalOrders = Order::count();
        $pendingPayments = Payment::where('status','pending')->count();
        $newMembers = User::where('role','member')->whereYear('created_at',date('Y'))->whereMonth('created_at',date('m'))->count();
        $totalMembers = User::where('role','member')->count();
        $lowStock = Inventory::whereColumn('quantity','<=','low_stock_threshold')->count();

        $monthly = Order::selectRaw('MONTH(created_at) m, SUM(total) total')
            ->whereIn('status',$validStatuses)->whereYear('created_at',$year)->groupBy('m')->pluck('total','m');
        $lastMonthly = Order::selectRaw('MONTH(created_at) m, SUM(total) total')
            ->whereIn('status',$validStatuses)->whereYear('created_at',$previousYear)->groupBy('m')->pluck('total','m');
        $monthlyOrders = Order::selectRaw('MONTH(created_at) m, COUNT(*) total')
            ->whereYear('created_at',$year)->groupBy('m')->pluck('total','m');
        $monthlyProducts = Product::selectRaw('MONTH(created_at) m, COUNT(*) total')
            ->whereYear('created_at',$year)->groupBy('m')->pluck('total','m');
        $monthlyCategories = Category::selectRaw('MONTH(created_at) m, COUNT(*) total')
            ->whereYear('created_at',$year)->groupBy('m')->pluck('total','m');

        $last7Sales = Order::selectRaw('DATE(created_at) d, SUM(total) total')
            ->whereIn('status',$validStatuses)->whereDate('created_at','>=',now()->subDays(6)->toDateString())
            ->groupBy('d')->pluck('total','d');

        $categorySales = Category::select('categories.name',DB::raw('COALESCE(SUM(order_items.total),0) total'))
            ->leftJoin('products','products.category_id','=','categories.id')
            ->leftJoin('order_items','order_items.product_id','=','products.id')
            ->groupBy('categories.id','categories.name')->pluck('total','name');

        $latestOrders = Order::with('user','payment')->latest()->take(6)->get();
        $lowStockItems = Inventory::with('product.category')->whereColumn('quantity','<=','low_stock_threshold')->orderBy('quantity')->take(6)->get();
        $latestProducts = Product::with('category','inventory')->latest()->take(8)->get();
        $bestProducts = OrderItem::select('product_id','product_name',DB::raw('SUM(quantity) qty'),DB::raw('SUM(total) revenue'))
            ->groupBy('product_id','product_name')->orderByDesc('qty')->take(5)->get();

        $notificationItems = [
            ['label'=>'สินค้าใกล้หมด','count'=>$lowStock,'route'=>route('admin.inventory.index',['low_stock'=>1]),'class'=>'warning'],
            ['label'=>'สลิปรอตรวจสอบ','count'=>$pendingPayments,'route'=>route('admin.payments.index'),'class'=>'danger'],
            ['label'=>'ออเดอร์ใหม่/รอชำระ','count'=>Order::where('status','pending_payment')->count(),'route'=>route('admin.orders.index',['status'=>'pending_payment']),'class'=>'info'],
        ];
        $years = range((int)date('Y')-3, (int)date('Y')+1);
        return view('admin.dashboard',compact('year','previousYear','years','totalSales','todaySales','monthSales','totalOrders','pendingPayments','newMembers','totalMembers','lowStock','monthly','lastMonthly','monthlyOrders','monthlyProducts','monthlyCategories','last7Sales','categorySales','latestOrders','lowStockItems','latestProducts','bestProducts','notificationItems'));
    }
}
