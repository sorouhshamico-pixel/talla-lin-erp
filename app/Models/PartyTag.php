<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartyTag extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'applies_to',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function suppliers()
    {
        return $this->hasMany(Supplier::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCustomers($query)
    {
        return $query->whereIn('applies_to', ['customer', 'both']);
    }

    public function scopeForSuppliers($query)
    {
        return $query->whereIn('applies_to', ['supplier', 'both']);
    }

    public function appliesToCustomers(): bool
    {
        return in_array($this->applies_to, ['customer', 'both'], true);
    }

    public function appliesToSuppliers(): bool
    {
        return in_array($this->applies_to, ['supplier', 'both'], true);
    }
}
