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
        'completion_note',
        'completed_by_user_id',
        'completed_at',
    ];

    protected $casts = [
        'follow_up_at' => 'date',
        'completed_at' => 'datetime',
    ];

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }
}
