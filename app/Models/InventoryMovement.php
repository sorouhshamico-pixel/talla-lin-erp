<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'warehouse_id',
        'product_id',
        'product_variant_id',
        'type',
        'direction',
        'quantity',
        'unit_cost',
        'reference_type',
        'reference_number',
        'notes',
        'occurred_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'occurred_at' => 'datetime',
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function displayType(): string
    {
        return match ($this->type) {
            'opening_balance' => 'رصيد افتتاحي',
            'purchase' => 'شراء',
            'sale' => 'بيع',
            'return' => 'مرتجع',
            'adjustment' => 'تسوية',
            'transfer_in' => 'تحويل وارد',
            'transfer_out' => 'تحويل صادر',
            'damage' => 'تالف',
            default => $this->type,
        };
    }

    public function displayDirection(): string
    {
        return match ($this->direction) {
            'in' => 'داخل',
            'out' => 'خارج',
            default => $this->direction,
        };
    }
}
