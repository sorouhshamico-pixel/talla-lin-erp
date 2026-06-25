<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'warehouse_id',
        'supplier_id',
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
        'invoice_date',
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
        'invoice_date' => 'datetime',
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

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function displayStatus(): string
    {
        return match ($this->status) {
            'draft' => 'مسودة',
            'received' => 'مستلمة',
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
