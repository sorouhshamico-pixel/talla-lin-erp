<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ReportSavedViewShareActivity extends Model
{
    public const UPDATED_AT = null;

    public const ACTION_SHARED = 'shared';

    public const ACTION_PERMISSION_UPDATED =
        'permission_updated';

    public const ACTION_REVOKED = 'revoked';

    public const ACTION_APPLIED = 'applied';

    public const ACTION_COPIED = 'copied';

    public const ACTION_SOURCE_ARCHIVED =
        'source_archived';

    public const ACTION_SOURCE_RESTORED =
        'source_restored';

    public const ACTION_SOURCE_DELETED =
        'source_deleted';

    public const ACTIONS = [
        self::ACTION_SHARED,
        self::ACTION_PERMISSION_UPDATED,
        self::ACTION_REVOKED,
        self::ACTION_APPLIED,
        self::ACTION_COPIED,
        self::ACTION_SOURCE_ARCHIVED,
        self::ACTION_SOURCE_RESTORED,
        self::ACTION_SOURCE_DELETED,
    ];

    protected $fillable = [
        'report_saved_view_share_id',
        'report_saved_view_id',
        'actor_user_id',
        'owner_user_id',
        'recipient_user_id',
        'action',
        'permission_before',
        'permission_after',
        'source_name_snapshot',
        'source_report_key_snapshot',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(
            fn (): never => throw new LogicException(
                'Sharing activity records are immutable.'
            )
        );

        static::deleting(
            fn (): never => throw new LogicException(
                'Sharing activity records are immutable.'
            )
        );
    }

    public function share(): BelongsTo
    {
        return $this->belongsTo(
            ReportSavedViewShare::class,
            'report_saved_view_share_id'
        );
    }

    public function savedView(): BelongsTo
    {
        return $this->belongsTo(
            ReportSavedView::class,
            'report_saved_view_id'
        );
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'actor_user_id'
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
}
