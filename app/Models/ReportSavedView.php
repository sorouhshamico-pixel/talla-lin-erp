<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ReportSavedView extends Model
{
    protected $fillable = [
        'user_id',
        'report_key',
        'name',
        'filters',
        'is_default',
        'archived_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'is_default' => 'boolean',
        'archived_at' => 'datetime',
    ];

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function isActive(): bool
    {
        return ! $this->isArchived();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            ReportSavedViewTag::class,
            'report_saved_view_tag'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
