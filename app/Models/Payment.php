<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Payment extends Model {
    protected $fillable=['order_id','method','amount','slip_path','status','verified_by','verified_at','reject_reason','transaction_ref','slip_review_status','slip_ocr_score','slip_ocr_text','slip_ocr_note']; protected $casts=['verified_at'=>'datetime','amount'=>'decimal:2']; public function order(){return $this->belongsTo(Order::class);} 
}
