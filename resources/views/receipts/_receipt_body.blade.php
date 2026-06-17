@php
$address = is_array($order->shipping_address_snapshot) ? $order->shipping_address_snapshot : json_decode($order->shipping_address_snapshot ?? '[]', true);
$customerName = $order->customer_tax_name ?: ($order->user->name ?? ($address['recipient_name'] ?? '-'));
$customerPhone = $address['phone'] ?? $order->user->phone ?? '-';
$customerEmail = $order->user->email ?? '-';
$customerAddress = $order->customer_tax_address ?: trim(($address['address'] ?? '').' '.($address['subdistrict'] ?? '').' '.($address['district'] ?? '').' '.($address['province'] ?? '').' '.($address['postal_code'] ?? ''));
$phones = collect([$company['company_phone_1'] ?? null,$company['company_phone_2'] ?? null,$company['company_phone_3'] ?? null])->filter()->implode(', ');
$paymentMethod = ['qr'=>'QR Code','bank_transfer'=>'โอนธนาคาร','cod'=>'เก็บเงินปลายทาง'][$order->payment->method ?? ''] ?? '-';
$vatRate = (float)($company['vat_rate'] ?? 7) ?: 7;
$beforeVat = (float)($order->before_vat_amount ?: round(max(($order->subtotal ?? $order->total - $order->shipping_fee),0) / (1 + ($vatRate/100)), 2));
$vatAmount = (float)($order->vat_amount ?: round(max(($order->subtotal ?? $order->total - $order->shipping_fee),0) - $beforeVat, 2));
$withholding = (float)($order->withholding_tax_amount ?: 0);
$netPay = (float)$order->total - $withholding;
$isEn = app()->getLocale() === 'en';
$displayCompanyName = $isEn ? ($company['company_name_en'] ?? $company['company_name']) : $company['company_name'];
$displayCompanyAddress = $isEn ? ($company['company_address_en'] ?? $company['company_address']) : $company['company_address'];
$logoSetting = $isEn ? ($company['receipt_logo_en'] ?? null) : ($company['receipt_logo_th'] ?? null);
$logoPath = public_path($logoSetting ?: ($company['receipt_logo'] ?? 'images/logo-th.png'));
$logoSrc = file_exists($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : '';
@endphp
<div class="receipt-a4">
    <div class="receipt-header-v2 compact-header">
        <div class="logo-panel company-under-logo">
            @if($logoSrc)<img class="receipt-logo-v2" src="{{$logoSrc}}" alt="logo">@endif
            <div class="company-info-lines mt-2">
                <div class="company-name">{{$displayCompanyName}}</div>
                <div class="company-subtitle">{{ $company['company_subtitle'] ?? '' }}</div>
                <div class="company-address-line"><span class="receipt-icon">📍</span><span>{{$displayCompanyAddress}}</span></div>
                <div class="company-address-line"><span class="receipt-icon">☎</span><span>{{$phones}}</span></div>
                <div class="company-address-line"><span class="receipt-icon">✉</span><span>{{$company['company_email'] ?: '-'}}</span></div>
                <div class="company-address-line"><span class="receipt-icon">เลขประจำตัวผู้เสียภาษี:</span><span>{{$company['company_tax_id'] ?: '-'}}</span></div>
            </div>
        </div>
        <div class="doc-right-panel">
            <div class="doc-title-v2 single-line-title">ใบเสร็จรับเงิน/ใบกำกับภาษี</div>
            <div class="doc-info-v2">
            <div><b>เลขที่เอกสาร :</b> {{$receiptNo}}</div>
            <div><b>วันที่ออก :</b> {{optional($order->updated_at ?? $order->created_at)->format('d/m/Y H:i')}}</div>
            <div><b>เลขอ้างอิง :</b> {{$order->order_number}}</div>
            <div><b>วิธีชำระ :</b> {{$paymentMethod}}</div>
            </div>
        </div>
    </div>

    <div class="buyer-box-v2">
        <h3>👤 ลูกค้า</h3>
        <div class="buyer-grid">
            <div><b>ชื่อ:</b> {{$customerName}}</div>
            <div><b>โทร:</b> {{$customerPhone}}</div>
            <div><b>Email:</b> {{$customerEmail}}</div>
            <div><b>เลขผู้เสียภาษี:</b> {{$order->needs_tax_invoice ? ($order->customer_tax_id ?: '-') : '-'}}</div>
        </div>
        <div><b>ที่อยู่:</b> {{$customerAddress ?: '-'}}</div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width:6%">#</th>
                <th>คำอธิบาย</th>
                <th style="width:14%">SKU</th>
                <th style="width:9%">จำนวน</th>
                <th style="width:12%">ราคา</th>
                <th style="width:10%">ส่วนลด</th>
                <th style="width:8%">VAT</th>
                <th style="width:14%">มูลค่ารวมภาษี</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $idx => $item)
            <tr>
                <td class="center">{{$idx+1}}</td>
                <td><b>{{$item->product_name}}</b></td>
                <td>{{$item->product->sku ?? '-'}}</td>
                <td class="center">{{$item->quantity}}</td>
                <td class="right">{{number_format($item->price,2)}}</td>
                <td class="right">0.00</td>
                <td class="center">{{$vatRate}}%</td>
                <td class="right">{{number_format($item->total,2)}}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary-section-v2">
        <div class="summary-left-v2">
            <h3>🧾 สรุป</h3>
            <div class="summary-row"><b>เงินมัดจำ</b><span>0.00 บาท</span></div>
            <div class="summary-row"><b>ส่วนลดรวม</b><span>0.00 บาท</span></div>
            <div class="summary-row"><b>มูลค่าที่คำนวณภาษี {{$vatRate}}%</b><span>{{number_format($beforeVat,2)}} บาท</span></div>
            <div class="summary-row"><b>ภาษีมูลค่าเพิ่ม {{$vatRate}}%</b><span>{{number_format($vatAmount,2)}} บาท</span></div>
            <div class="summary-row"><b>ค่าจัดส่ง</b><span>{{number_format($order->shipping_fee,2)}} บาท</span></div>
            <div class="summary-row"><b>ภาษีหัก ณ ที่จ่าย</b><span>{{number_format($withholding,2)}} บาท</span></div>
            <div class="summary-row total-line"><b>จำนวนเงินทั้งสิ้น</b><span>{{number_format($order->total,2)}} บาท</span></div>
            <div class="text-baht">{{number_format($netPay,2)}} บาทถ้วน</div>
        </div>
        <div class="summary-total-box-v2">
            <div class="label">จำนวนเงินทั้งสิ้น</div>
            <div class="amount">{{number_format($order->total,2)}} <span>บาท</span></div>
            <hr>
            <div class="summary-row"><b>จำนวนเงินที่ลูกค้า ณ ที่จ่าย</b><span>{{number_format($withholding,2)}} บาท</span></div>
            <div class="summary-row pay-line"><b>จำนวนเงินที่ชำระ</b><span>{{number_format($netPay,2)}} บาท</span></div>
        </div>
    </div>

    <div class="payment-note-v2">
        <div><b>💳 ชำระเงิน</b> วันที่ชำระ: {{optional($order->payment->updated_at ?? $order->updated_at)->format('d/m/Y')}}</div>
        <div><b>จำนวนเงินรวม:</b> {{number_format($order->total,2)}} บาท</div>
        <div><b>หมายเหตุค่าจัดส่ง:</b> {{$order->shipping_rule_note ?: '-'}}</div>
    </div>

    <div class="signature-section-v2 clean-signatures">
        <div class="signature-box-v2">
            <div class="sign-line"></div>
            <div class="sig-title">ลงชื่อผู้ขาย</div>
            <div class="small-muted">( {{auth()->user()->name ?? 'ผู้ขาย'}} )</div>
            <div>วันที่ ____/____/________</div>
        </div>
        <div class="signature-box-v2">
            <div class="sign-line"></div>
            <div class="sig-title">ลงชื่อผู้ซื้อ/ลูกค้า</div>
            <div class="small-muted">( {{$customerName}} )</div>
            <div>วันที่ ____/____/________</div>
        </div>
    </div>
    <p class="fine-print">หากพบข้อผิดพลาดในเอกสาร กรุณาแจ้งกลับภายใน 2 วัน หากพ้นกำหนด ทางร้านขอสงวนสิทธิ์ไม่รับผิดชอบทุกกรณี</p>
</div>
