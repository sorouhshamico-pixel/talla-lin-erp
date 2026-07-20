<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ReportSavedViewShareActivityRetentionExecution extends Model
{
    public const TYPE_MANUAL_PREVIEW = 'manual_preview';
    public const TYPE_MANUAL_EXECUTION = 'manual_execution';
    public const TYPE_SCHEDULED_EXECUTION = 'scheduled_execution';
    public const TYPE_COMMAND_EXECUTION = 'command_execution';

    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CONFLICTED = 'conflicted';

    protected $fillable = [
        'type', 'status', 'actor_user_id', 'requested_days',
        'requested_chunk_size', 'candidate_count', 'deleted_count',
        'cutoff_at', 'duration_ms', 'failure_class', 'failure_message',
        'context', 'started_at', 'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'cutoff_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn (): never => throw new LogicException(
            'Retention execution history rows are immutable.'
        ));

        static::deleting(fn (): never => throw new LogicException(
            'Retention execution history rows cannot be deleted.'
        ));
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
