<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['order_date' => 'date'];
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}
