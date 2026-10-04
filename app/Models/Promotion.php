<?php
namespace App\Models;
use App\Models\Concerns\BelongsToCurrentStore; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Promotion extends Model { use BelongsToCurrentStore; protected $fillable = ['store_id','product_id','name','type','value','member_tier','starts_at','ends_at','is_active']; protected function casts(): array { return ['value'=>'integer','starts_at'=>'datetime','ends_at'=>'datetime','is_active'=>'boolean']; } public function product(): BelongsTo { return $this->belongsTo(Product::class); } }
