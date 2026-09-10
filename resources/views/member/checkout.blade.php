@extends('layouts.app')
@section('content')
@php
    $subtotal = $summary['subtotal'] ?? $cart->items->sum(fn($i)=>$i->price*$i->quantity);
    $shipping = $summary['shipping'] ?? 50;
    $total = $summary['grand_total'] ?? ($subtotal+$shipping);
    $beforeVat = $summary['before_vat'] ?? round($total/1.07,2);
    $vat = $summary['vat'] ?? round($total-$beforeVat,2);
    $withholding = $summary['withholding'] ?? 0;
    $vatRate = $summary['vat_rate'] ?? 7;
    $shippingNote = $summary['shipping_note'] ?? 'เชียงใหม่ 50 / ต่างจังหวัด 100 / มากกว่า 10 ชิ้น +50';
    if(app()->getLocale()==='en'){
        $shippingNote = str_replace(['เชียงใหม่ 50 / ต่างจังหวัด 100 / มากกว่า 10 ชิ้น +50','เชียงใหม่ 50 บาท','ต่างจังหวัด 100 บาท','มากกว่า 10 ชิ้น +50 บาท'], ['Chiang Mai 50 / Other provinces 100 / More than 10 items +50','Chiang Mai 50 THB','Other provinces 100 THB','More than 10 items +50 THB'], $shippingNote);
    }
@endphp
<div class="container">
    <div class="mb-4">
        <h2 class="fw-bold mb-1">{{__('Checkout')}}</h2>
        <p class="text-muted mb-0">{{__('Select address, review products, and choose payment method')}}</p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger rounded-4 shadow-sm">
            <div class="fw-bold mb-1">{{__('Please check your information before placing order')}}</div>
            <ul class="mb-0">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="post" enctype="multipart/form-data" action="{{route('member.checkout.store')}}">
        @csrf
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="bg-white rounded-4 shadow-sm p-4 mb-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-truck me-2 text-danger"></i>{{__('Shipping Name and Address')}}</h5>
                    @if(isset($addresses) && $addresses->count())
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{__('Choose address from My Account')}}</label>
                        <select class="form-select rounded-pill" id="savedAddressSelect">
                            <option value="">{{__('-- Enter new address --')}}</option>
                            @foreach($addresses as $addr)
                            <option value="{{$addr->id}}" @selected(optional($defaultAddress)->id===$addr->id)
                                data-recipient="{{$addr->recipient_name}}" data-phone="{{$addr->phone}}" data-address="{{$addr->address}}"
                                data-province="{{$addr->province}}" data-district="{{$addr->district}}" data-subdistrict="{{$addr->subdistrict}}" data-postal="{{$addr->postal_code}}">
                                {{$addr->recipient_name}} | {{$addr->phone}} | {{$addr->address}} {{$addr->subdistrict}} {{$addr->district}} {{$addr->province}} {{$addr->postal_code}} @if($addr->is_default)({{__('Default')}})@endif
                            </option>
                            @endforeach
                        </select>
                        <div class="form-text">{{__('To add or edit addresses, go to')}} <a href="{{route('member.profile')}}#address">{{__('My Account')}}</a></div>
                    </div>
                    @else
                    <div class="alert alert-warning rounded-4">{{__('No saved address. You can enter a new one below or add a permanent address at')}} <a href="{{route('member.profile')}}#address">{{__('My Account')}}</a></div>
                    @endif
                    <div class="row g-3" data-thai-address id="checkoutAddressBox">
                        <div class="col-md-6"><label class="form-label">{{__('Recipient Name')}}</label><input id="recipientName" class="form-control rounded-pill" name="recipient_name" value="{{old('recipient_name',optional($defaultAddress)->recipient_name ?? auth()->user()->name)}}" required></div>
                        <div class="col-md-6"><label class="form-label">{{__('Phone')}}</label><input id="recipientPhone" class="form-control rounded-pill" name="phone" value="{{old('phone',optional($defaultAddress)->phone ?? auth()->user()->phone)}}" required inputmode="numeric" pattern="[0-9]{9,10}" maxlength="10" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)"></div>
                        <div class="col-12"><label class="form-label">{{__('Address / House No. / Village / Alley / Road')}}</label><textarea id="recipientAddress" class="form-control rounded-4" name="address" rows="3" required>{{old('address',optional($defaultAddress)->address ?? auth()->user()->address)}}</textarea></div>
                        <div class="col-md-3"><label class="form-label">{{__('Province')}}</label><select id="recipientProvince" class="form-select rounded-pill" name="province" data-province data-value="{{old('province',optional($defaultAddress)->province)}}" required></select></div>
                        <div class="col-md-3"><label class="form-label">{{__('District')}}</label><select id="recipientDistrict" class="form-select rounded-pill" name="district" data-district data-value="{{old('district',optional($defaultAddress)->district)}}" required></select></div>
                        <div class="col-md-3"><label class="form-label">{{__('Subdistrict')}}</label><select id="recipientSubdistrict" class="form-select rounded-pill" name="subdistrict" data-subdistrict data-value="{{old('subdistrict',optional($defaultAddress)->subdistrict)}}" required></select></div>
                        <div class="col-md-3"><label class="form-label">{{__('Postal Code')}}</label><input id="recipientPostal" class="form-control rounded-pill bg-light" name="postal_code" data-postal data-value="{{old('postal_code',optional($defaultAddress)->postal_code)}}" readonly required></div>
                    </div>
                </div>

                <div class="bg-white rounded-4 shadow-sm p-4 mb-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-file-earmark-text me-2 text-danger"></i>{{__('Tax Invoice Information')}}</h5>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="needsTaxInvoice" name="needs_tax_invoice" value="1" @checked(old('needs_tax_invoice'))>
                        <label class="form-check-label fw-bold" for="needsTaxInvoice">{{__('I need a tax invoice / receipt with tax ID')}}</label>
                    </div>
                    <div id="taxInvoiceBox" class="row g-3" style="display:none">
                        <div class="col-md-6"><label class="form-label">{{__('Taxpayer Name')}}</label><input class="form-control rounded-pill" name="customer_tax_name" value="{{old('customer_tax_name', auth()->user()->name)}}" placeholder="{{__('Individual or company name')}}"></div>
                        <div class="col-md-6"><label class="form-label">{{__('Tax ID')}}</label><input class="form-control rounded-pill" name="customer_tax_id" value="{{old('customer_tax_id')}}" placeholder="{{__('13 digits')}}" maxlength="13" minlength="13" inputmode="numeric" pattern="[0-9]{13}" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,13)"></div>
                        <div class="col-12"><label class="form-label">{{__('Tax Invoice Address')}}</label><textarea class="form-control rounded-4" name="customer_tax_address" rows="3" placeholder="{{__('Address for tax invoice')}}">{{old('customer_tax_address')}}</textarea><div class="form-text">{{__('If left blank, the shipping address will be used on the receipt.')}}</div></div>
                    </div>
                </div>
                <div class="bg-white rounded-4 shadow-sm p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-credit-card me-2 text-danger"></i>{{__('Payment Method')}}</h5>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="payment-card w-100"><input class="form-check-input me-2 payment-radio" type="radio" name="payment_method" value="qr" checked> QR Code</label></div>
                        <div class="col-md-4"><label class="payment-card w-100"><input class="form-check-input me-2 payment-radio" type="radio" name="payment_method" value="bank_transfer"> {{__('Bank Transfer')}}</label></div>
                        <div class="col-md-4"><label class="payment-card w-100"><input class="form-check-input me-2 payment-radio" type="radio" name="payment_method" value="cod"> {{__('Cash on Delivery')}}</label></div>
                    </div>
                    <div id="qrBox" class="payment-info mt-4 text-center"><div class="small text-muted">{{__('Grand Total')}}</div><div class="fs-4 text-danger fw-bold mb-2" data-summary-total>฿{{number_format($total,2)}}</div><img src="{{asset('promptpay_qr.jpg')}}" class="img-fluid rounded-4 border" style="max-width:330px" alt="PromptPay QR"><p class="mt-3 mb-0 text-muted">{{__('Scan QR code to transfer, then upload the payment slip below')}}</p></div>
                    <div id="bankBox" class="payment-info mt-4" style="display:none">
                        <div class="bank-transfer-card rounded-4 p-4">
                            <div class="d-flex align-items-center gap-3 mb-3"><div class="kbank-icon">K</div><div><h5 class="fw-bold mb-0">{{__('Kasikorn Bank')}}</h5><div class="text-muted small">{{__('Bank Transfer Account')}}</div></div></div>
                            <div class="row g-3 align-items-center"><div class="col-md-7"><div class="small text-muted">{{__('Account Number')}}</div><div class="fs-3 fw-bold bank-number">080-188-2323</div><div class="mt-2"><span class="text-muted">{{__('Account Name')}}:</span> <b>จิรวัฒน์ โปธา</b></div></div><div class="col-md-5 text-md-end"><div class="small text-muted">{{__('Grand Total')}}</div><div class="fs-3 fw-bold text-danger" data-summary-total>฿{{number_format($total,2)}}</div><div class="small text-muted mt-1">{{__('Please transfer the exact amount and upload the slip')}}</div></div></div>
                        </div>
                    </div>
                    <div id="codBox" class="payment-info mt-4" style="display:none"><div class="alert alert-warning rounded-4 mb-0">เลือก{{__('Cash on Delivery')}} ระบบจะให้แอดมินตรวจสอบและจัดส่งตามสถานะคำสั่งซื้อ</div></div>
                    <div id="slipBox" class="mt-4"><label class="form-label fw-bold">{{__('Upload Payment Slip')}}</label><input type="file" name="slip" class="form-control rounded-pill" accept="image/*"><div class="form-text">{{__('Image file only, maximum 4MB')}}</div></div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="bg-white rounded-4 shadow-sm p-4 sticky-top checkout-summary-card" style="top:100px">
                    <h5 class="fw-bold mb-3"><i class="bi bi-basket me-2 text-danger"></i>{{__('Order Items')}}</h5>
                    <div class="table-responsive"><table class="table align-middle"><thead class="table-light"><tr><th>{{__('Product')}}</th><th class="text-center">{{__('Quantity')}}</th><th class="text-end">{{__('Total')}}</th></tr></thead><tbody>@foreach($cart->items as $i)<tr><td><b>{{$i->product->display_name}}</b><div class="small text-muted">฿{{number_format($i->price,2)}} / {{__('pieces')}}</div></td><td class="text-center">{{$i->quantity}}</td><td class="text-end text-danger fw-bold">฿{{number_format($i->price*$i->quantity,2)}}</td></tr>@endforeach</tbody></table></div>
                    <div class="d-flex justify-content-between"><span>ราคาก่อน VAT</span><span data-summary-before-vat>฿{{number_format($beforeVat,2)}}</span></div>
                    <div class="d-flex justify-content-between mt-1"><span>VAT {{$vatRate}}%</span><span data-summary-vat>฿{{number_format($vat,2)}}</span></div>
                    <div class="d-flex justify-content-between mt-2"><span class="fw-bold">ราคารวมสินค้า</span><b data-summary-subtotal>฿{{number_format($subtotal,2)}}</b></div><hr>
                    <div class="d-flex justify-content-between mt-2"><span>{{__('Shipping Fee')}}<div class="small text-muted" data-summary-shipping-note>{{$shippingNote}}</div></span><b data-summary-shipping>฿{{number_format($shipping,2)}}</b></div>
                    <div class="d-flex justify-content-between mt-1"><span>{{__('Withholding Tax')}}</span><span data-summary-withholding>฿{{number_format($withholding,2)}}</span></div><hr>
                    <div class="d-flex justify-content-between fs-5"><span class="fw-bold">{{__('Grand Total')}}</span><b class="text-danger" data-summary-total>฿{{number_format($total,2)}}</b></div>
                    <div class="small text-muted mt-2" id="summaryStatus" role="status" aria-live="polite"></div>
                    <div class="checkout-submit-wrap"><button class="btn btn-brand btn-lg rounded-pill w-100 mt-4 js-place-order-btn"><i class="bi bi-check2-circle"></i> {{__('Place Order')}}</button></div>
                    <a class="btn btn-outline-secondary rounded-pill w-100 mt-2" href="{{route('member.cart')}}">{{__('Back to Cart')}}</a>
                </div>
            </div>
        </div>
    </form>
</div>
<style>.payment-card{border:1px solid #e2e8f0;border-radius:18px;padding:1rem;background:#fff;font-weight:700;cursor:pointer}.payment-card:hover{border-color:var(--brand);background:#fff7f7}.bank-transfer-card{background:linear-gradient(135deg,#eefaf2,#ffffff);border:1px solid #bfe6ce}.kbank-icon{width:64px;height:64px;border-radius:18px;background:#138f4d;color:#fff;font-size:2rem;font-weight:900;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 18px rgba(19,143,77,.25)}.bank-number{letter-spacing:.5px;color:#0b6b3b}</style>
@endsection
@push('scripts')
<script src="{{asset('js/thai-address.js')}}"></script>
<script>
function togglePayment(){ const val=document.querySelector('.payment-radio:checked')?.value; document.getElementById('qrBox').style.display=val==='qr'?'block':'none'; document.getElementById('bankBox').style.display=val==='bank_transfer'?'block':'none'; document.getElementById('codBox').style.display=val==='cod'?'block':'none'; document.getElementById('slipBox').style.display=(val==='qr'||val==='bank_transfer')?'block':'none'; }
document.querySelectorAll('.payment-radio').forEach(r=>r.addEventListener('change',togglePayment));togglePayment();
const savedAddressSelect=document.getElementById('savedAddressSelect');
async function fillSavedAddress(){ if(!savedAddressSelect) return; const opt=savedAddressSelect.options[savedAddressSelect.selectedIndex]; if(!opt || !opt.value) return; document.getElementById('recipientName').value=opt.dataset.recipient || ''; document.getElementById('recipientPhone').value=opt.dataset.phone || ''; document.getElementById('recipientAddress').value=opt.dataset.address || ''; const box=document.getElementById('checkoutAddressBox'); box.querySelector('[data-province]').dataset.value=opt.dataset.province || ''; box.querySelector('[data-district]').dataset.value=opt.dataset.district || ''; box.querySelector('[data-subdistrict]').dataset.value=opt.dataset.subdistrict || ''; box.querySelector('[data-postal]').dataset.value=opt.dataset.postal || ''; await window.initThaiAddressCascader(box); }
const money=value=>'฿'+Number(value||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
let summaryTimer;
async function refreshOrderSummary(){
    const province=document.getElementById('recipientProvince')?.value || '';
    const status=document.getElementById('summaryStatus');
    if(status) status.textContent='กำลังคำนวณยอดล่าสุด…';
    try{
        const response=await fetch('{{route('member.checkout.summary')}}?province='+encodeURIComponent(province),{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
        const data=await response.json();
        if(!response.ok) throw new Error(data.message || 'คำนวณยอดไม่สำเร็จ');
        document.querySelectorAll('[data-summary-total]').forEach(el=>el.textContent=money(data.grand_total));
        document.querySelectorAll('[data-summary-before-vat]').forEach(el=>el.textContent=money(data.before_vat));
        document.querySelectorAll('[data-summary-vat]').forEach(el=>el.textContent=money(data.vat));
        document.querySelectorAll('[data-summary-subtotal]').forEach(el=>el.textContent=money(data.subtotal));
        document.querySelectorAll('[data-summary-shipping]').forEach(el=>el.textContent=money(data.shipping));
        document.querySelectorAll('[data-summary-withholding]').forEach(el=>el.textContent=money(data.withholding));
        document.querySelectorAll('[data-summary-shipping-note]').forEach(el=>el.textContent=data.shipping_note || '');
        if(status) status.textContent='อัปเดตยอดตามที่อยู่จัดส่งแล้ว';
    }catch(error){ if(status) status.textContent=error.message; }
}
function queueSummaryRefresh(){ clearTimeout(summaryTimer); summaryTimer=setTimeout(refreshOrderSummary,250); }
if(savedAddressSelect){ savedAddressSelect.addEventListener('change',async()=>{await fillSavedAddress(); queueSummaryRefresh();}); fillSavedAddress().then(queueSummaryRefresh); }
document.getElementById('recipientProvince')?.addEventListener('change',queueSummaryRefresh);
function toggleTaxInvoice(){ const box=document.getElementById('taxInvoiceBox'); const on=document.getElementById('needsTaxInvoice')?.checked; if(box) box.style.display=on?'flex':'none'; }
document.getElementById('needsTaxInvoice')?.addEventListener('change',toggleTaxInvoice); toggleTaxInvoice();
</script>
@endpush
