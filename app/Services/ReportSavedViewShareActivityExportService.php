<?php

namespace App\Services;

use App\Models\ReportSavedViewShareActivity;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportSavedViewShareActivityExportService
{
    private const COLUMNS = [
        'activity_id',
        'created_at',
        'action',
        'source_saved_view_id',
        'source_name',
        'source_report_key',
        'actor_user_id',
        'actor_name',
        'owner_user_id',
        'owner_name',
        'recipient_user_id',
        'recipient_name',
        'permission_before',
        'permission_after',
        'copied_saved_view_id',
    ];

    public function ownerCsv(
        User $owner,
        array $filters = []
    ): StreamedResponse {
        $query = $this->baseQuery()
            ->where(
                'activities.owner_user_id',
                $owner->id
            );

        $this->applyFilters(
            $query,
            $filters,
            true
        );

        return $this->stream(
            $query,
            'saved-view-sharing-activity-owner-'
        );
    }

    public function recipientCsv(
        User $recipient,
        array $filters = []
    ): StreamedResponse {
        $query = $this->baseQuery()
            ->where(
                'activities.recipient_user_id',
                $recipient->id
            );

        $this->applyFilters(
            $query,
            $filters,
            false
        );

        return $this->stream(
            $query,
            'saved-view-sharing-activity-recipient-'
        );
    }

    private function baseQuery(): Builder
    {
        return DB::table(
            'report_saved_view_share_activities as activities'
        )
            ->leftJoin(
                'users as actor_users',
                'actor_users.id',
                '=',
                'activities.actor_user_id'
            )
            ->leftJoin(
                'users as owner_users',
                'owner_users.id',
                '=',
                'activities.owner_user_id'
            )
            ->leftJoin(
                'users as recipient_users',
                'recipient_users.id',
                '=',
                'activities.recipient_user_id'
            )
            ->select([
                'activities.id',
                'activities.created_at',
                'activities.action',
                'activities.report_saved_view_id',
                'activities.source_name_snapshot',
                'activities.source_report_key_snapshot',
                'activities.actor_user_id',
                'actor_users.name as actor_name',
                'activities.owner_user_id',
                'owner_users.name as owner_name',
                'activities.recipient_user_id',
                'recipient_users.name as recipient_name',
                'activities.permission_before',
                'activities.permission_after',
                'activities.metadata',
            ])
            ->orderByDesc('activities.created_at')
            ->orderByDesc('activities.id');
    }

    private function applyFilters(
        Builder $query,
        array $filters,
        bool $allowRecipientFilter
    ): void {
        $action = trim(
            (string) ($filters['action'] ?? '')
        );

        if (
            in_array(
                $action,
                ReportSavedViewShareActivity::ACTIONS,
                true
            )
        ) {
            $query->where(
                'activities.action',
                $action
            );
        }

        $savedViewId = (int) (
            $filters['report_saved_view_id'] ?? 0
        );

        if ($savedViewId > 0) {
            $query->where(
                'activities.report_saved_view_id',
                $savedViewId
            );
        }

        $recipientUserId = (int) (
            $filters['recipient_user_id'] ?? 0
        );

        if (
            $allowRecipientFilter
            && $recipientUserId > 0
        ) {
            $query->where(
                'activities.recipient_user_id',
                $recipientUserId
            );
        }

        $dateFrom = trim(
            (string) ($filters['date_from'] ?? '')
        );

        if ($this->isDate($dateFrom)) {
            $query->whereDate(
                'activities.created_at',
                '>=',
                $dateFrom
            );
        }

        $dateTo = trim(
            (string) ($filters['date_to'] ?? '')
        );

        if ($this->isDate($dateTo)) {
            $query->whereDate(
                'activities.created_at',
                '<=',
                $dateTo
            );
        }
    }

    private function stream(
        Builder $query,
        string $filenamePrefix
    ): StreamedResponse {
        $filename = $filenamePrefix
            . now()->format('Ymd-His')
            . '.csv';

        return response()->streamDownload(
            function () use ($query): void {
                $handle = fopen(
                    'php://output',
                    'wb'
                );

                if ($handle === false) {
                    return;
                }

                fwrite(
                    $handle,
                    "\xEF\xBB\xBF"
                );

                fputcsv(
                    $handle,
                    self::COLUMNS,
                    ',',
                    '"',
                    '\\',
                    "\n"
                );

                foreach ($query->cursor() as $activity) {
                    fputcsv(
                        $handle,
                        $this->row($activity),
                        ',',
                        '"',
                        '\\',
                        "\n"
                    );
                }

                fclose($handle);
            },
            $filename,
            [
                'Content-Type' =>
                    'text/csv; charset=UTF-8',
                'Cache-Control' =>
                    'no-store, no-cache, must-revalidate',
            ]
        );
    }

    private function row(object $activity): array
    {
        $metadata = json_decode(
            (string) ($activity->metadata ?? ''),
            true
        );

        if (! is_array($metadata)) {
            $metadata = [];
        }

        return [
            $activity->id,
            $activity->created_at,
            $activity->action,
            $activity->report_saved_view_id,
            $activity->source_name_snapshot,
            $activity->source_report_key_snapshot,
            $activity->actor_user_id,
            $activity->actor_name,
            $activity->owner_user_id,
            $activity->owner_name,
            $activity->recipient_user_id,
            $activity->recipient_name,
            $activity->permission_before,
            $activity->permission_after,
            $metadata['copied_saved_view_id'] ?? null,
        ];
    }

    private function isDate(string $value): bool
    {
        return preg_match(
            '/^\d{4}-\d{2}-\d{2}$/',
            $value
        ) === 1;
    }
}
