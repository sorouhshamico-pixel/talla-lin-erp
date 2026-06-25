<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'expense_category_id',
        'user_id',
        'code',
        'description',
        'amount',
        'tax_amount',
        'payment_method',
        'expense_date',
        'reference_number',
        'notes',
        'attachment_path',
        'attachment_original_name',
        'is_paid',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'expense_date' => 'date',
        'is_paid' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function displayPaymentMethod(): string
    {
        return match ($this->payment_method) {
            'cash' => 'نقدًا',
            'card' => 'بطاقة',
            'bank_transfer' => 'تحويل بنكي',
            'online' => 'دفع إلكتروني',
            'other' => 'أخرى',
            default => $this->payment_method,
        };
    }

    public function hasAttachment(): bool
    {
        return ! empty($this->attachment_path);
    }

    public function attachmentUrl(): ?string
    {
        if (! $this->hasAttachment()) {
            return null;
        }

        return Storage::disk('public')->url($this->attachment_path);
    }
}
