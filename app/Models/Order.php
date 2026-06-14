<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'user_id','order_number','status','payment_status','subtotal','shipping_fee','vat_amount','before_vat_amount','withholding_tax_amount','shipping_rule_note','total','shipping_address_snapshot','needs_tax_invoice','customer_tax_id','customer_tax_name','customer_tax_address','expires_at','ordered_at'
    ];

    protected $casts = [
        'expires_at','ordered_at' => 'datetime',
        'expires_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'before_vat_amount' => 'decimal:2',
        'withholding_tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];


    public static function generateOrderNumber(): string
    {
        do {
            $number = 'MYH' . now()->format('YmdHis') . strtoupper(Str::random(6));
        } while (self::where('order_number', $number)->exists());

        return $number;
    }

    public function user(){ return $this->belongsTo(User::class); }
    public function items(){ return $this->hasMany(OrderItem::class); }
    public function payment(){ return $this->hasOne(Payment::class); }
    public function shipment(){ return $this->hasOne(Shipment::class); }

    public static function statusLabels(): array
    {
        return app()->getLocale() === 'en'
            ? [
                'pending_payment' => 'Pending Payment',
                'paid' => 'Paid',
                'preparing' => 'Preparing',
                'packed' => 'Packed',
                'shipped' => 'Shipping',
                'delivered' => 'Delivered',
                'delivery_failed' => 'Delivery Failed',
                'cancelled' => 'Cancelled',
            ]
            : [
                'pending_payment' => 'รอชำระเงิน',
                'paid' => 'ชำระเงินแล้ว',
                'preparing' => 'เตรียมสินค้า',
                'packed' => 'จัดเสร็จแล้ว',
                'shipped' => 'กำลังจัดส่ง',
                'delivered' => 'จัดส่งแล้ว',
                'delivery_failed' => 'จัดส่งไม่สำเร็จ',
                'cancelled' => 'ยกเลิก',
            ];
    }

    public static function paymentStatusLabels(): array
    {
        return app()->getLocale() === 'en'
            ? [
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                'paid' => 'Paid',
            ]
            : [
                'pending' => 'รอตรวจสอบ',
                'approved' => 'อนุมัติแล้ว',
                'rejected' => 'ถูกปฏิเสธ',
                'paid' => 'ชำระแล้ว',
            ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusLabels()[$this->status] ?? (string) $this->status;
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return self::paymentStatusLabels()[$this->payment_status] ?? (string) $this->payment_status;
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return [
            'pending_payment' => 'text-bg-warning',
            'paid' => 'text-bg-success',
            'preparing' => 'text-bg-info',
            'packed' => 'text-bg-secondary',
            'shipped' => 'text-bg-primary',
            'delivered' => 'text-bg-success',
            'delivery_failed' => 'text-bg-danger',
            'cancelled' => 'text-bg-dark',
        ][$this->status] ?? 'text-bg-light';
    }

    public function getPaymentStatusBadgeClassAttribute(): string
    {
        return [
            'pending' => 'text-bg-warning',
            'approved' => 'text-bg-success',
            'paid' => 'text-bg-success',
            'rejected' => 'text-bg-danger',
        ][$this->payment_status] ?? 'text-bg-light';
    }
}
