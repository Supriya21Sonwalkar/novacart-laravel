<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InwardItem extends Model
{
    protected $table = 'inward_items';

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'ordered_qty' => 'integer',
        'received_qty' => 'integer',

        'unit_cost' => 'integer',

        'gst_percent' => 'float',

        'other_charges' => 'integer',

        'subtotal' => 'integer',
        'tax' => 'integer',
        'total' => 'integer',
    ];

    /**
     * This item belongs to an inward record.
     */
    public function inward()
    {
        return $this->belongsTo(
            Inward::class,
            'inward_id'
        );
    }

    /**
     * This item belongs to a product.
     */
    public function product()
    {
        return $this->belongsTo(
            Product::class,
            'product_id'
        );
    }
}