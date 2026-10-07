<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Inward extends Model
{
    protected $table = 'inwards';

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'inward_date' => 'date',
        'invoice_date' => 'date',

        'subtotal' => 'integer',
        'tax' => 'integer',
        'other_charges' => 'integer',
        'total' => 'integer',
    ];

    /**
     * An inward contains multiple product items.
     */
    public function items()
    {
        return $this->hasMany(
            InwardItem::class,
            'inward_id'
        );
    }
}