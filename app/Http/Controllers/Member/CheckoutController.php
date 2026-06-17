<?php
namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\{Cart,Order,OrderItem,Payment,Shipment,InventoryLog,ShippingAddress,Setting};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\ValidatesThaiAddress;
use Illuminate\Validation\ValidationException;
use App\Services\SlipOcrService;

class CheckoutController extends Controller
{
    use ValidatesThaiAddress;
    public function form()
    {
        $cart = Cart::with('items.product')->firstOrCreate(['user_id' => auth()->id()]);
        if ($cart->items->isEmpty()) {
            return redirect()->route('member.cart')->with('error', __('Your cart is empty. Please choose products before checkout.'));
        }

        $addresses = ShippingAddress::where('user_id', auth()->id())->orderByDesc('is_default')->latest()->get();
        $defaultAddress = $addresses->firstWhere('is_default', true) ?: $addresses->first();
        $summary = $this->orderSummary($cart, optional($defaultAddress)->province);

        return view('member.checkout', compact('cart', 'addresses', 'defaultAddress', 'summary'));
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'recipient_name' => 'required|string|max:255',
            'phone' => ['required','regex:/^[0-9]{9,10}$/'],
            'address' => 'required|string|max:1000',
            'province' => 'required|string|max:100',
            'district' => 'required|string|max:100',
            'subdistrict' => 'required|string|max:100',
            'postal_code' => ['required','regex:/^[0-9]{5}$/'],
            'payment_method' => 'required|in:bank_transfer,qr,cod',
            'slip' => 'required_if:payment_method,bank_transfer,qr|nullable|image|mimes:jpg,jpeg,png|max:5120',
            'needs_tax_invoice' => 'nullable|boolean',
            'customer_tax_id' => ['required_if:needs_tax_invoice,1','nullable','digits:13'],
            'customer_tax_name' => 'nullable|string|max:255',
            'customer_tax_address' => 'nullable|string|max:1000',
        ], [
            'recipient_name.required' => 'กรุณากรอกชื่อผู้รับ',
            'phone.required' => 'กรุณากรอกเบอร์โทร',
            'phone.regex' => 'เบอร์โทรต้องเป็นตัวเลขเท่านั้น 9-10 หลัก',
            'address.required' => 'กรุณากรอกที่อยู่',
            'province.required' => 'กรุณาเลือกจังหวัด',
            'district.required' => 'กรุณาเลือกอำเภอ/เขต',
            'subdistrict.required' => 'กรุณาเลือกตำบล/แขวง',
            'postal_code.required' => 'กรุณาเลือกรหัสไปรษณีย์',
            'postal_code.regex' => 'รหัสไปรษณีย์ต้องเป็นตัวเลข 5 หลัก',
            'payment_method.required' => 'กรุณาเลือกวิธีชำระเงิน',
            'slip.required_if' => 'กรุณาอัปโหลดสลิปเมื่อเลือก QR Code หรือโอนผ่านธนาคาร',
            'slip.image' => 'สลิปต้องเป็นไฟล์รูปภาพเท่านั้น',
            'slip.mimes' => 'สลิปต้องเป็นไฟล์ jpg, jpeg หรือ png เท่านั้น',
            'slip.max' => 'สลิปต้องมีขนาดไม่เกิน 5MB',
            'customer_tax_id.required_if' => 'กรุณากรอกเลขประจำตัวผู้เสียภาษี',
            'customer_tax_id.digits' => 'เลขประจำตัวผู้เสียภาษีต้องเป็นตัวเลข 13 หลัก',
        ]);

        $d = $this->validateThaiAddressOrFail($d, true);

        $slipAnalysis = null;
        if ($r->hasFile('slip')) {
            $slipAnalysis = app(SlipOcrService::class)->analyze($r->file('slip')->getRealPath());
            if (($slipAnalysis['status'] ?? null) === 'invalid') {
                throw ValidationException::withMessages([
                    'slip' => 'รูปที่อัปโหลดไม่พบข้อมูลที่คล้ายสลิปโอนเงิน กรุณาอัปโหลดสลิปจากธนาคารหรือ PromptPay ใหม่',
                ]);
            }
        }

        $cart = Cart::with('items.product.inventory')->where('user_id', auth()->id())->first();
        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route('member.cart')->with('error', __('Your cart is empty. Please choose products before checkout.'));
        }

        foreach ($cart->items as $item) {
            $available = (int) optional($item->product->inventory)->quantity;
            if ($available < $item->quantity) {
                return response()->view('member.stock_error', [
                    'item' => $item,
                    'product' => $item->product,
                    'available' => $available,
                    'requested' => $item->quantity,
                ], 200);
            }
        }

        return DB::transaction(function () use ($d, $r, $slipAnalysis) {
            // Edge case: lock ตะกร้าใน Transaction ป้องกัน double submit / กด Checkout ซ้ำพร้อมกัน
            $cart = Cart::where('user_id', auth()->id())->lockForUpdate()->first();
            if (!$cart) {
                return redirect()->route('member.cart')->with('error', __('Your cart is empty. Please choose products before checkout.'));
            }
            $cart->load('items.product.inventory');
            if ($cart->items->isEmpty()) {
                return redirect()->route('member.orders')->with('success', 'คำสั่งซื้อล่าสุดถูกสร้างแล้ว หรือไม่มีสินค้าในตะกร้า');
            }

            foreach ($cart->items as $item) {
                $inventory = \App\Models\Inventory::where('product_id', $item->product_id)->lockForUpdate()->first();
                $available = (int) optional($inventory)->quantity;
                if (!$item->product || $item->product->status !== 'active') {
                    return response()->view('member.stock_error', [
                        'item' => $item,
                        'product' => $item->product,
                        'available' => 0,
                        'requested' => $item->quantity,
                    ], 200);
                }
                if ($item->quantity < 1 || $item->quantity > 999 || $available < $item->quantity) {
                    return response()->view('member.stock_error', [
                        'item' => $item,
                        'product' => $item->product,
                        'available' => $available,
                        'requested' => $item->quantity,
                    ], 200);
                }
            }

            $summary = $this->orderSummary($cart, $d['province'] ?? null);

            $order = Order::create([
                'user_id' => auth()->id(),
                'order_number' => Order::generateOrderNumber(),
                'status' => 'pending_payment',
                'payment_status' => 'pending',
                'subtotal' => $summary['subtotal'],
                'shipping_fee' => $summary['shipping'],
                'vat_amount' => $summary['vat'],
                'before_vat_amount' => $summary['before_vat'],
                'withholding_tax_amount' => $summary['withholding'],
                'shipping_rule_note' => $summary['shipping_note'],
                'total' => $summary['grand_total'],
                'shipping_address_snapshot' => json_encode($d, JSON_UNESCAPED_UNICODE),
                'needs_tax_invoice' => (bool)($d['needs_tax_invoice'] ?? false),
                'customer_tax_id' => $d['customer_tax_id'] ?? null,
                'customer_tax_name' => $d['customer_tax_name'] ?? null,
                'customer_tax_address' => $d['customer_tax_address'] ?: null,
                'ordered_at' => now(),
                'expires_at' => now()->addDay(),
            ]);

            foreach ($cart->items as $i) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $i->product_id,
                    'product_name' => $i->product->display_name,
                    'quantity' => $i->quantity,
                    'price' => $i->price,
                    'total' => $i->price * $i->quantity,
                ]);

                $inv = \App\Models\Inventory::where('product_id', $i->product_id)->lockForUpdate()->first();
                $inv->decrement('quantity', $i->quantity);
                InventoryLog::create([
                    'inventory_id' => $inv->id,
                    'type' => 'order_deduct',
                    'quantity' => $i->quantity,
                    'note' => 'ตัดสต๊อกจากคำสั่งซื้อ ' . $order->order_number,
                    'user_id' => auth()->id(),
                ]);
            }

            $slip = $r->file('slip') ? $r->file('slip')->store('payment_slips', 'public') : null;
            Payment::create([
                'order_id' => $order->id,
                'method' => $d['payment_method'],
                'amount' => $order->total,
                'slip_path' => $slip,
                'status' => 'pending',
                'slip_review_status' => $slipAnalysis['status'] ?? ($slip ? 'needs_review' : 'not_required'),
                'slip_ocr_score' => $slipAnalysis['score'] ?? 0,
                'slip_ocr_text' => $slipAnalysis['text'] ?? null,
                'slip_ocr_note' => $slipAnalysis['note'] ?? null,
            ]);
            Shipment::create(['order_id' => $order->id]);
            $cart->items()->delete();

            return redirect()->route('member.orders')->with('success', __('Order created successfully'));
        });
    }

    private function orderSummary(Cart $cart, ?string $province = null): array
    {
        $subtotal = (float) $cart->items->sum(fn ($i) => $i->price * $i->quantity);
        $qty = (int) $cart->items->sum('quantity');
        $shipping = $this->shippingFee($province, $qty);
        // ราคาสินค้าเป็นราคาที่รวม VAT แล้ว ส่วนค่าจัดส่งบวกแยกทีหลัง
        $grand = $subtotal + $shipping;
        $vatRate = (float) Setting::getValue('vat_rate', '7');
        $withholdingRate = (float) Setting::getValue('withholding_tax_rate', '0');
        $beforeVat = $vatRate > 0 ? round($subtotal / (1 + ($vatRate / 100)), 2) : $subtotal;
        $vat = round($subtotal - $beforeVat, 2);
        $withholding = $withholdingRate > 0 ? round($beforeVat * ($withholdingRate / 100), 2) : 0;

        return [
            'subtotal' => $subtotal,
            'quantity' => $qty,
            'shipping' => $shipping,
            'grand_total' => $grand,
            'before_vat' => $beforeVat,
            'vat' => $vat,
            'withholding' => $withholding,
            'vat_rate' => $vatRate,
            'withholding_rate' => $withholdingRate,
            'shipping_note' => $this->shippingNote($province, $qty),
        ];
    }

    private function shippingFee(?string $province, int $qty): float
    {
        $isChiangMai = in_array(trim((string)$province), ['เชียงใหม่','Chiang Mai','chiang mai'], true);
        $fee = $isChiangMai ? 50 : 100;
        if ($qty > 10) $fee += 50;
        return $fee;
    }

    private function shippingNote(?string $province, int $qty): string
    {
        $isChiangMai = in_array(trim((string)$province), ['เชียงใหม่','Chiang Mai','chiang mai'], true);
        $note = $isChiangMai ? 'เชียงใหม่เริ่มต้น 50 บาท' : 'ต่างจังหวัด 100 บาท';
        if ($qty > 10) $note .= ' + สินค้ามากกว่า 10 ชิ้น เพิ่ม 50 บาท';
        return $note;
    }
}
