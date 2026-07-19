<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportSavedViewShare extends Model
{
    public const PERMISSION_VIEW = 'view';

    public const PERMISSION_USE = 'use';

    protected $fillable = [
        'report_saved_view_id',
        'owner_user_id',
        'recipient_user_id',
        'permission',
    ];

    public function savedView(): BelongsTo
    {
        return $this->belongsTo(
            ReportSavedView::class,
            'report_saved_view_id'
        );
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'owner_user_id'
        );
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'recipient_user_id'
        );
    }

    public function canUse(): bool
    {
        return $this->permission === self::PERMISSION_USE;
    }
}
