<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Order,Product,Inventory,Payment,User,OrderItem};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $range = $request->get('range','30');
        $from = match($range){
            'today' => today(),
            '7' => now()->subDays(6)->startOfDay(),
            '30' => now()->subDays(29)->startOfDay(),
            default => $request->filled('from') ? $request->date('from') : now()->subDays(29)->startOfDay(),
        };
        $to = $request->filled('to') ? $request->date('to')->endOfDay() : now()->endOfDay();
        $validStatuses = ['paid','preparing','packed','shipped','delivered'];
        $ordersQuery = Order::whereBetween('created_at',[$from,$to]);
        $paidOrdersQuery = (clone $ordersQuery)->whereIn('status',$validStatuses);

        $sales = (clone $paidOrdersQuery)->sum('total');
        $shipping = (clone $paidOrdersQuery)->sum('shipping_fee');
        $orders = (clone $ordersQuery)->count();
        $itemsSold = OrderItem::whereHas('order', fn($q)=>$q->whereBetween('created_at',[$from,$to])->whereIn('status',$validStatuses))->sum('quantity');
        $products = Product::count();
        $members = User::where('role','member')->count();
        $payments = Payment::whereBetween('created_at',[$from,$to])->count();
        $costTotal = OrderItem::whereHas('order', fn($q)=>$q->whereBetween('created_at',[$from,$to])->whereIn('status',$validStatuses))
            ->join('products','products.id','=','order_items.product_id')
            ->selectRaw('COALESCE(SUM(products.cost * order_items.quantity),0) cost')->value('cost') ?? 0;
        $grossProfit = $sales - $costTotal;

        $lowStockItems = Inventory::with('product.category')->whereColumn('quantity','<=','low_stock_threshold')->orderBy('quantity')->limit(10)->get();
        $bestProducts = OrderItem::select('product_id','product_name',DB::raw('SUM(quantity) qty'),DB::raw('SUM(total) revenue'))
            ->whereHas('order', fn($q)=>$q->whereBetween('created_at',[$from,$to])->whereIn('status',$validStatuses))
            ->groupBy('product_id','product_name')->orderByDesc('qty')->limit(10)->get();
        $topCustomers = Order::select('user_id',DB::raw('COUNT(*) order_count'),DB::raw('SUM(total) spending'))
            ->with('user')->whereBetween('created_at',[$from,$to])->whereIn('status',$validStatuses)
            ->groupBy('user_id')->orderByDesc('spending')->limit(10)->get();
        $paymentMethods = Payment::select('method',DB::raw('COUNT(*) count'),DB::raw('SUM(amount) amount'))
            ->whereBetween('created_at',[$from,$to])->groupBy('method')->get();
        $orderStatusCounts = Order::select('status',DB::raw('COUNT(*) count'))->whereBetween('created_at',[$from,$to])->groupBy('status')->pluck('count','status');
        $receiptOrders = Order::with('user')->whereBetween('created_at',[$from,$to])->whereIn('status',$validStatuses)->latest()->limit(10)->get();

        return view('admin.reports.index', compact('range','from','to','sales','shipping','orders','itemsSold','products','members','payments','costTotal','grossProfit','lowStockItems','bestProducts','topCustomers','paymentMethods','orderStatusCounts','receiptOrders'));
    }
}
