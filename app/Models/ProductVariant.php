<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'color',
        'size',
        'sale_price',
        'cost_price',
        'is_active',
    ];

    protected $casts = [
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function displayName(): string
    {
        return trim(collect([$this->color, $this->size])->filter()->join(' - '));
    }

    public function inventoryBalances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class, 'product_variant_id');
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'product_variant_id');
    }

    public function salesInvoiceItems(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class, 'product_variant_id');
    }
}
