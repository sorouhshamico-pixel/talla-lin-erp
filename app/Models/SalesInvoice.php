<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'customer_id',
        'delivery_note_id',
        'user_id',
        'invoice_number',
        'status',
        'payment_status',
        'currency',
        'subtotal',
        'discount_total',
        'tax_total',
        'grand_total',
        'paid_amount',
        'remaining_amount',
        'issued_at',
        'due_at',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'issued_at' => 'datetime',
        'due_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function deliveryNote(): BelongsTo
    {
        return $this->belongsTo(DeliveryNote::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalesInvoicePayment::class);
    }

    public function collectionNotes(): HasMany
    {
        return $this->hasMany(SalesInvoiceCollectionNote::class);
    }

    public function displayStatus(): string
    {
        return match ($this->status) {
            'draft' => 'مسودة',
            'issued' => 'معتمدة',
            'paid' => 'مدفوعة',
            'cancelled' => 'ملغاة',
            default => $this->status,
        };
    }

    public function displayPaymentStatus(): string
    {
        return match ($this->payment_status) {
            'unpaid' => 'غير مدفوعة',
            'partial' => 'مدفوعة جزئيًا',
            'paid' => 'مدفوعة بالكامل',
            default => $this->payment_status,
        };
    }
}
