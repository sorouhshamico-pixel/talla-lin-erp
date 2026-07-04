<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    protected $fillable = [
        'sales_order_number',
        'quotation_id',
        'customer_id',
        'sales_order_date',
        'status',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'sales_order_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SalesOrderItem::class);
    }
}
