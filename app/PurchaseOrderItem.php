<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['quantity' => 'integer', 'received_quantity' => 'integer', 'existing_stock' => 'integer'];
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
    public function getPendingQuantityAttribute()
    {
        return max(0, $this->quantity - $this->received_quantity);
    }
}
