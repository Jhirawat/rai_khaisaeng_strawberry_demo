@extends('layouts.admin')
@section('title','Dashboard')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h4 class="fw-bold mb-0">ภาพรวมธุรกิจและรายงานสำคัญ</h4><div class="text-muted">สรุปยอดขาย ออเดอร์ สต๊อก และข้อมูลสำหรับวางแผนโปรโมชัน</div></div>
    <form method="get" class="d-flex gap-2"><select name="year" class="form-select rounded-pill" onchange="this.form.submit()">@foreach($years as $y)<option value="{{$y}}" @selected($year==$y)>ปี {{$y}}</option>@endforeach</select></form>
</div>
<div class="row g-3">
    <div class="col-md-3"><div class="content-card metric-card p-3">ยอดขายทั้งหมด<h3 class="text-danger">฿{{number_format($totalSales,2)}}</h3></div></div>
    <div class="col-md-3"><div class="content-card metric-card info p-3">ยอดขายวันนี้<h3>฿{{number_format($todaySales,2)}}</h3></div></div>
    <div class="col-md-3"><div class="content-card metric-card p-3">ยอดขายเดือนนี้<h3>฿{{number_format($monthSales,2)}}</h3></div></div>
    <div class="col-md-3"><div class="content-card metric-card warning p-3">สินค้าใกล้หมด<h3>{{$lowStock}}</h3></div></div>
    <div class="col-md-3"><div class="content-card p-3">คำสั่งซื้อทั้งหมด<h3>{{$totalOrders}}</h3></div></div>
    <div class="col-md-3"><div class="content-card p-3">สลิปรอตรวจสอบ<h3 class="text-danger">{{$pendingPayments}}</h3></div></div>
    <div class="col-md-3"><div class="content-card p-3">สมาชิกทั้งหมด<h3>{{$totalMembers}}</h3></div></div>
    <div class="col-md-3"><div class="content-card p-3">ลูกค้าใหม่เดือนนี้<h3>{{$newMembers}}</h3></div></div>
</div>
<div class="row mt-4 g-3">
    <div class="col-lg-8"><div class="content-card p-3"><div class="fw-bold mb-2">ยอดขายรายเดือน ปี {{$year}} เทียบปี {{$previousYear}}</div><canvas id="salesByYear" height="115"></canvas></div></div>
    <div class="col-lg-4"><div class="content-card p-3"><div class="fw-bold mb-2">ยอดขาย 7 วันล่าสุด</div><canvas id="last7" height="220"></canvas></div></div>
</div>
<div class="row mt-4 g-3">
    <div class="col-lg-4"><div class="content-card p-4 h-100"><h5 class="fw-bold mb-3">แจ้งเตือนที่ต้องจัดการ</h5>@foreach($notificationItems as $n)<a href="{{$n['route']}}" class="d-flex justify-content-between align-items-center border rounded-4 p-3 mb-2 text-decoration-none text-dark"><span>{{$n['label']}}</span><span class="badge rounded-pill text-bg-{{$n['class']}}">{{$n['count']}}</span></a>@endforeach</div></div>
    <div class="col-lg-4"><div class="content-card p-4 h-100"><h5 class="fw-bold mb-3">สินค้าขายดี Top 5</h5>@forelse($bestProducts as $p)<div class="d-flex justify-content-between border-bottom py-2"><span>{{$p->product_name}}</span><b>{{$p->qty}} ชิ้น</b></div>@empty<div class="text-muted">ยังไม่มีข้อมูลขาย</div>@endforelse</div></div>
    <div class="col-lg-4"><div class="content-card p-4 h-100"><h5 class="fw-bold mb-3">Promotion Insight</h5><ul class="mb-0"><li>จัดโปรสินค้าขายดีเป็นชุด Bundle</li><li>ตรวจสินค้าใกล้หมดก่อนเริ่มโปรโมชัน</li><li>ดูเดือนที่ยอดขายตกเพื่อจัดส่งฟรี/ส่วนลด</li><li>ติดตามสลิปรอตรวจสอบทุกวัน</li></ul></div></div>
</div>
<div class="row mt-4 g-3">
    <div class="col-lg-8"><div class="content-card p-3"><div class="fw-bold mb-2">แนวโน้มผลิตภัณฑ์ตามเดือน</div><canvas id="productTrend" height="120"></canvas><div class="small text-muted mt-2">เส้นการผลิต = จำนวนสินค้าที่เพิ่ม, เส้นการขาย = จำนวนคำสั่งซื้อ, เส้นประเภททั้งหมด = จำนวนหมวดหมู่ใหม่</div></div></div>
    <div class="col-lg-4"><div class="content-card p-3"><div class="fw-bold mb-2">ยอดขายตามประเภทสินค้า</div><canvas id="cats" height="230"></canvas></div></div>
</div>
<div class="row mt-4 g-3">
    <div class="col-lg-7"><div class="content-card p-3"><div class="fw-bold mb-2">ออเดอร์ล่าสุด</div><table class="table align-middle"><tr><th>เลขออเดอร์</th><th>ลูกค้า</th><th>ยอดเงิน</th><th>สถานะ</th></tr>@foreach($latestOrders as $o)<tr><td><a href="{{route('admin.orders.show',$o)}}">{{$o->order_number}}</a></td><td>{{$o->user->name ?? '-'}}</td><td class="text-danger fw-bold">฿{{number_format($o->total,2)}}</td><td><span class="badge {{$o->status_badge_class}}">{{$o->status_label}}</span></td></tr>@endforeach</table></div></div>
    <div class="col-lg-5"><div class="content-card p-3"><div class="fw-bold mb-2">สินค้าใกล้หมด</div><table class="table align-middle"><tr><th>SKU</th><th>สินค้า</th><th class="text-end">คงเหลือ</th></tr>@forelse($lowStockItems as $i)<tr><td>{{$i->product->sku ?? '-'}}</td><td>{{$i->product->name ?? '-'}}</td><td class="text-end text-danger fw-bold">{{$i->quantity}}</td></tr>@empty<tr><td colspan="3" class="text-center text-muted">ไม่มีสินค้าใกล้หมด</td></tr>@endforelse</table><a href="{{route('admin.inventory.index',['low_stock'=>1])}}" class="btn btn-outline-warning rounded-pill">ดูทั้งหมด</a></div></div>
</div>
<script>
const months=['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
const currentSales=@json(array_map(fn($m)=>(float)($monthly[$m]??0),range(1,12)));
const lastSales=@json(array_map(fn($m)=>(float)($lastMonthly[$m]??0),range(1,12)));
new Chart(document.getElementById('salesByYear'),{type:'line',data:{labels:months,datasets:[{label:'ยอดขายปี {{$year}}',data:currentSales,tension:.35},{label:'ยอดขายปี {{$previousYear}}',data:lastSales,tension:.35}]},options:{responsive:true,plugins:{tooltip:{callbacks:{label:c=>c.dataset.label+': ฿'+Number(c.raw).toLocaleString()}}},scales:{x:{title:{display:true,text:'เดือน'}},y:{title:{display:true,text:'ยอดขาย (บาท)'}}}}});
let last7Labels=[];let last7Data=[];@for($i=6;$i>=0;$i--) last7Labels.push('{{now()->subDays($i)->format('d/m')}}'); last7Data.push({{(float)($last7Sales[now()->subDays($i)->toDateString()]??0)}}); @endfor
new Chart(document.getElementById('last7'),{type:'bar',data:{labels:last7Labels,datasets:[{label:'ยอดขาย',data:last7Data}]},options:{plugins:{legend:{display:false}},scales:{y:{ticks:{callback:v=>'฿'+v}}}}});
new Chart(document.getElementById('productTrend'),{type:'line',data:{labels:months,datasets:[{label:'เส้นการผลิต',data:@json(array_map(fn($m)=>(int)($monthlyProducts[$m]??0),range(1,12))),tension:.35},{label:'เส้นการขาย',data:@json(array_map(fn($m)=>(int)($monthlyOrders[$m]??0),range(1,12))),tension:.35},{label:'เส้นประเภททั้งหมด',data:@json(array_map(fn($m)=>(int)($monthlyCategories[$m]??0),range(1,12))),tension:.35}]},options:{scales:{x:{title:{display:true,text:'เดือน'}},y:{title:{display:true,text:'จำนวน'}}}}});
new Chart(document.getElementById('cats'),{type:'doughnut',data:{labels:@json($categorySales->keys()),datasets:[{data:@json($categorySales->values())}]}});
</script>
@endsection
