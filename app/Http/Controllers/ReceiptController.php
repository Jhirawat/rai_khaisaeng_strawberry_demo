<?php
namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;

class ReceiptController extends Controller
{
    private function authorizeMember(Order $order): void
    {
        $user = auth()->user();
        $isBackoffice = $user && in_array($user->role, ['admin','super_admin'], true);

        if (!$isBackoffice && $order->user_id !== auth()->id()) {
            abort(403);
        }
    }

    private function loadReceiptOrder(Order $order): Order
    {
        $order->load('items.product','user','payment','shipment');
        return $order;
    }

    private function canIssue(Order $order): bool
    {
        return in_array($order->status, ['paid','preparing','packed','shipped','delivered'], true)
            || in_array($order->payment_status, ['approved','paid'], true);
    }

    public function show(Order $order)
    {
        $this->authorizeMember($order);
        $order = $this->loadReceiptOrder($order);
        abort_unless($this->canIssue($order), 403, 'ยังไม่สามารถออกใบเสร็จได้');
        return view('receipts.show', ['order'=>$order, 'receiptNo'=>$this->receiptNo($order), 'company'=>$this->company()]);
    }

    public function download(Order $order)
    {
        $this->authorizeMember($order);
        $order = $this->loadReceiptOrder($order);
        abort_unless($this->canIssue($order), 403, 'ยังไม่สามารถออกใบเสร็จได้');
        $receiptNo = $this->receiptNo($order);
        $company = $this->company();
        $pdf = Pdf::loadView('receipts.pdf', compact('order','receiptNo','company'))->setPaper('a4');
        return $pdf->download($receiptNo.'.pdf');
    }


    private function company(): array
    {
        $defaults = [
            'company_name' => 'ไร่ไขแสงสตรอเบอร์รี่',
            'company_name_en' => 'Rai Khaisaeng Strawberry',
            'company_subtitle' => 'วิสาหกิจชุมชนแปรรูปสตรอเบอร์รี่',
            'company_address' => '134 หมู่ 4 ตำบลบ่อแก้ว อำเภอสะเมิง จังหวัดเชียงใหม่ 50250',
            'company_address_en' => '134 Moo 4, Bo Kaeo Subdistrict, Samoeng District, Chiang Mai 50250',
            'company_tax_id' => '111xxxxxxxxxx',
            'company_email' => 'info@khaisaeng-strawberry.test',
            'company_phone_1' => '089-999-8295',
            'company_phone_2' => '081-033-4893',
            'company_phone_3' => '089-265-5686',
            'receipt_logo' => 'images/logo-th.png',
            'receipt_logo_th' => 'images/logo-th.png',
            'receipt_logo_en' => 'images/logo-en.png',
        ];
        foreach ($defaults as $key => $default) {
            $defaults[$key] = Setting::getValue($key, $default);
        }
        return $defaults;
    }

    private function receiptNo(Order $order): string
    {
        return 'RC'.optional($order->created_at)->format('Ymd').str_pad((string)$order->id, 5, '0', STR_PAD_LEFT);
    }
}
