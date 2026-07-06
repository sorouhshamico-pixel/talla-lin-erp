<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesInvoiceCollectionNote extends Model
{
    protected $fillable = [
        'sales_invoice_id',
        'user_id',
        'note',
        'follow_up_at',
    ];

    protected $casts = [
        'follow_up_at' => 'date',
    ];

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
