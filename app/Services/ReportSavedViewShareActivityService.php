<?php

namespace App\Services;

use App\Models\ReportSavedView;
use App\Models\ReportSavedViewShare;
use App\Models\ReportSavedViewShareActivity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReportSavedViewShareActivityService
{
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
