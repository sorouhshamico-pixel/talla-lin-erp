<?php

namespace App\Services;

use App\Models\ReportSavedView;
use App\Models\ReportSavedViewShare;
use App\Models\ReportSavedViewShareActivity;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReportSavedViewShareActivityService
{
    public function paginateForOwner(
        User $owner,
        ?string $action = null,
        ?int $recipientUserId = null,
        ?int $savedViewId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        int $perPage = 25
    ): LengthAwarePaginator {
        $query = ReportSavedViewShareActivity::query()
            ->where('owner_user_id', $owner->id);

        $this->applyFilters(
            $query,
            $action,
            $recipientUserId,
            $savedViewId,
            $dateFrom,
            $dateTo
        );

        return $this->paginate($query, $perPage);
    }

    public function paginateForRecipient(
        User $recipient,
        ?string $action = null,
        ?int $savedViewId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        int $perPage = 25
    ): LengthAwarePaginator {
        $query = ReportSavedViewShareActivity::query()
            ->where(
                'recipient_user_id',
                $recipient->id
            );

        $this->applyFilters(
            $query,
            $action,
            null,
            $savedViewId,
            $dateFrom,
            $dateTo
        );

        return $this->paginate($query, $perPage);
    }

    public function record(
        string $action,
        ?User $actor = null,
        ?User $owner = null,
        ?User $recipient = null,
        ?ReportSavedView $savedView = null,
        ?ReportSavedViewShare $share = null,
        ?string $permissionBefore = null,
        ?string $permissionAfter = null,
        array $metadata = []
    ): ReportSavedViewShareActivity {
        $this->validateAction($action);
        $this->validatePermission(
            $permissionBefore
        );
        $this->validatePermission(
            $permissionAfter
        );

        return DB::transaction(
            function () use (
                $action,
                $actor,
                $owner,
                $recipient,
                $savedView,
                $share,
                $permissionBefore,
                $permissionAfter,
                $metadata
            ): ReportSavedViewShareActivity {
                return ReportSavedViewShareActivity::query()
                    ->create([
                        'report_saved_view_share_id' =>
                            $share?->id,
                        'report_saved_view_id' =>
                            $savedView?->id,
                        'actor_user_id' =>
                            $actor?->id,
                        'owner_user_id' =>
                            $owner?->id,
                        'recipient_user_id' =>
                            $recipient?->id,
                        'action' => $action,
                        'permission_before' =>
                            $permissionBefore,
                        'permission_after' =>
                            $permissionAfter,
                        'source_name_snapshot' =>
                            $savedView?->name,
                        'source_report_key_snapshot' =>
                            $savedView?->report_key,
                        'metadata' =>
                            $metadata === []
                                ? null
                                : $metadata,
                        'created_at' => now(),
                    ]);
            }
        );
    }

    private function applyFilters(
        Builder $query,
        ?string $action,
        ?int $recipientUserId,
        ?int $savedViewId,
        ?string $dateFrom,
        ?string $dateTo
    ): void {
        if (
            is_string($action)
            && in_array(
                trim($action),
                ReportSavedViewShareActivity::ACTIONS,
                true
            )
        ) {
            $query->where('action', trim($action));
        }

        if (($recipientUserId ?? 0) > 0) {
            $query->where(
                'recipient_user_id',
                $recipientUserId
            );
        }

        if (($savedViewId ?? 0) > 0) {
            $query->where(
                'report_saved_view_id',
                $savedViewId
            );
        }

        if (
            is_string($dateFrom)
            && preg_match(
                '/^\d{4}-\d{2}-\d{2}$/',
                $dateFrom
            ) === 1
        ) {
            $query->whereDate(
                'created_at',
                '>=',
                $dateFrom
            );
        }

        if (
            is_string($dateTo)
            && preg_match(
                '/^\d{4}-\d{2}-\d{2}$/',
                $dateTo
            ) === 1
        ) {
            $query->whereDate(
                'created_at',
                '<=',
                $dateTo
            );
        }
    }

    private function paginate(
        Builder $query,
        int $perPage
    ): LengthAwarePaginator {
        $perPage = max(
            5,
            min($perPage, 100)
        );

        return $query
            ->with([
                'actor',
                'owner',
                'recipient',
                'savedView',
                'share',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    private function validateAction(
        string $action
    ): void {
        if (
            ! in_array(
                $action,
                ReportSavedViewShareActivity::ACTIONS,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid sharing activity action.'
            );
        }
    }

    private function validatePermission(
        ?string $permission
    ): void {
        if (
            $permission !== null
            && ! in_array(
                $permission,
                ['view', 'use'],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid sharing activity permission.'
            );
        }
    }
}
