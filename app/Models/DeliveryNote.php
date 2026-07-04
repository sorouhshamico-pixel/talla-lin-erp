<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryNote extends Model
{
    protected $fillable = [
        'delivery_note_number',
        'sales_order_id',
        'customer_id',
        'delivery_note_date',
        'status',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'delivery_note_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(DeliveryNoteItem::class);
    }
}
