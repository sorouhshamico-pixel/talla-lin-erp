<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartyContactLog extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'customer_id',
        'supplier_id',
        'contact_type',
        'summary',
        'contacted_at',
        'follow_up_at',
    ];

    protected $casts = [
        'contacted_at' => 'date',
        'follow_up_at' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
