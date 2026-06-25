<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseInvoicePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_invoice_id',
        'user_id',
        'amount',
        'method',
        'reference_number',
        'notes',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function displayMethod(): string
    {
        return match ($this->method) {
            'cash' => 'نقدًا',
            'card' => 'بطاقة',
            'bank_transfer' => 'تحويل بنكي',
            'online' => 'دفع إلكتروني',
            'other' => 'أخرى',
            default => $this->method,
        };
    }
}
