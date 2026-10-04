<?php
namespace App\Models;
use App\Models\Concerns\BelongsToCurrentStore; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo; use Illuminate\Database\Eloquent\Relations\HasMany;
class SaleReturn extends Model { use BelongsToCurrentStore; protected $fillable = ['store_id','sale_id','return_number','status','refund_total','processed_by','reason','refunded_at']; protected function casts(): array { return ['refund_total'=>'integer','refunded_at'=>'datetime']; } public function sale(): BelongsTo { return $this->belongsTo(Sale::class); } public function items(): HasMany { return $this->hasMany(SaleReturnItem::class); } }
