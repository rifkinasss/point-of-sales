<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SaleReturnItem extends Model { protected $fillable = ['sale_return_id','sale_item_id','product_id','quantity','refund_amount','restock']; protected function casts(): array { return ['quantity'=>'integer','refund_amount'=>'integer','restock'=>'boolean']; } public function saleReturn(): BelongsTo { return $this->belongsTo(SaleReturn::class); } }
