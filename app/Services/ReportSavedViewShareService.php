<?php

namespace App\Services;

use App\Models\ReportSavedView;
use App\Models\ReportSavedViewShare;
use App\Models\ReportSavedViewShareActivity;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReportSavedViewShareService
{
    public function __construct(
        private readonly ReportSavedViewShareActivityService
            $activityService
    ) {
    }

    public function listRecipients(
        User $owner,
        ReportSavedView $savedView
    ): Collection {
        $this->authorizeOwner(
            $owner,
            $savedView
        );

        return ReportSavedViewShare::query()
            ->where(
                'report_saved_view_id',
                $savedView->id
            )
            ->where(
                'owner_user_id',
                $owner->id
            )
            ->with('recipient')
            ->orderBy('recipient_user_id')
            ->orderBy('id')
            ->get();
    }

    public function share(
        User $owner,
        ReportSavedView $savedView,
        User $recipient,
        string $permission
    ): ReportSavedViewShare {
        $this->authorizeOwner(
            $owner,
            $savedView
        );

        if (
            (int) $owner->id
            === (int) $recipient->id
        ) {
            throw ValidationException::withMessages([
                'recipient_user_id' =>
                    'لا يمكن مشاركة العرض المحفوظ مع مالكه.',
            ]);
        }

        $permission = $this->validatePermission(
            $permission
        );

        return DB::transaction(
            function () use (
                $owner,
                $savedView,
                $recipient,
                $permission
            ): ReportSavedViewShare {
                $share = ReportSavedViewShare::query()
                    ->where(
                        'report_saved_view_id',
                        $savedView->id
                    )
                    ->where(
                        'recipient_user_id',
                        $recipient->id
                    )
                    ->lockForUpdate()
                    ->first();

                if ($share === null) {
                    $share = ReportSavedViewShare::query()
                        ->create([
                            'report_saved_view_id' =>
                                $savedView->id,
                            'owner_user_id' =>
                                $owner->id,
                            'recipient_user_id' =>
                                $recipient->id,
                            'permission' =>
                                $permission,
                        ]);

                    $this->activityService->record(
                        ReportSavedViewShareActivity::ACTION_SHARED,
                        $owner,
                        $owner,
                        $recipient,
                        $savedView,
                        $share,
                        null,
                        $permission
                    );

                    return $share->refresh();
                }

                $permissionBefore =
                    $share->permission;

                if (
                    $permissionBefore
                    === $permission
                ) {
                    return $share->refresh();
                }

                $share->forceFill([
                    'owner_user_id' => $owner->id,
                    'permission' => $permission,
                ])->save();

                $this->activityService->record(
                    ReportSavedViewShareActivity::
                        ACTION_PERMISSION_UPDATED,
                    $owner,
                    $owner,
                    $recipient,
                    $savedView,
                    $share,
                    $permissionBefore,
                    $permission
                );

                return $share->refresh();
            }
        );
    }

    public function updatePermission(
        User $owner,
        ReportSavedViewShare $share,
        string $permission
    ): ReportSavedViewShare {
        $this->authorizeShareOwner(
            $owner,
            $share
        );

        $permission = $this->validatePermission(
            $permission
        );

        return DB::transaction(
            function () use (
                $owner,
                $share,
                $permission
            ): ReportSavedViewShare {
                $lockedShare = ReportSavedViewShare::query()
                    ->whereKey($share->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->authorizeShareOwner(
                    $owner,
                    $lockedShare
                );

                $lockedShare->loadMissing([
                    'recipient',
                    'savedView',
                ]);

                $permissionBefore =
                    $lockedShare->permission;

                if (
                    $permissionBefore
                    === $permission
                ) {
                    return $lockedShare->refresh();
                }

                $lockedShare->forceFill([
                    'permission' => $permission,
                ])->save();

                $this->activityService->record(
                    ReportSavedViewShareActivity::
                        ACTION_PERMISSION_UPDATED,
                    $owner,
                    $owner,
                    $lockedShare->recipient,
                    $lockedShare->savedView,
                    $lockedShare,
                    $permissionBefore,
                    $permission
                );

                return $lockedShare->refresh();
            }
        );
    }

    public function revoke(
        User $owner,
        ReportSavedViewShare $share
    ): bool {
        $this->authorizeShareOwner(
            $owner,
            $share
        );

        return DB::transaction(
            function () use (
                $owner,
                $share
            ): bool {
                $lockedShare = ReportSavedViewShare::query()
                    ->whereKey($share->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->authorizeShareOwner(
                    $owner,
                    $lockedShare
                );

                $lockedShare->loadMissing([
                    'recipient',
                    'savedView',
                ]);

                $this->activityService->record(
                    ReportSavedViewShareActivity::ACTION_REVOKED,
                    $owner,
                    $owner,
                    $lockedShare->recipient,
                    $lockedShare->savedView,
                    $lockedShare,
                    $lockedShare->permission,
                    null
                );

                return (bool) $lockedShare->delete();
            }
        );
    }

    public function listReceived(
        User $recipient
    ): Collection {
        return ReportSavedViewShare::query()
            ->where(
                'recipient_user_id',
                $recipient->id
            )
            ->whereHas(
                'savedView',
                fn ($query) => $query->whereNull(
                    'archived_at'
                )
            )
            ->with([
                'owner',
                'savedView',
            ])
            ->orderByDesc('id')
            ->get();
    }

    public function copyToRecipient(
        User $recipient,
        ReportSavedViewShare $share
    ): ReportSavedView {
        $share = $this->receivedShare(
            $recipient,
            $share
        );

        return DB::transaction(
            function () use (
                $recipient,
                $share
            ): ReportSavedView {
                $lockedShare =
                    ReportSavedViewShare::query()
                        ->whereKey($share->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                $lockedShare = $this->receivedShare(
                    $recipient,
                    $lockedShare
                );

                $source = $lockedShare->savedView;

                $copy = ReportSavedView::query()->create([
                    'user_id' => $recipient->id,
                    'report_key' => $source->report_key,
                    'name' => $this->copyName(
                        $recipient,
                        $source->name
                    ),
                    'filters' => $source->filters ?? [],
                    'is_default' => false,
                    'archived_at' => null,
                ]);

                $this->activityService->record(
                    ReportSavedViewShareActivity::ACTION_COPIED,
                    $recipient,
                    $lockedShare->owner,
                    $recipient,
                    $source,
                    $lockedShare,
                    $lockedShare->permission,
                    $lockedShare->permission,
                    [
                        'copied_saved_view_id' =>
                            $copy->id,
                    ]
                );

                return $copy;
            }
        );
    }

    public function recordApplied(
        User $recipient,
        ReportSavedViewShare $share
    ): ReportSavedViewShare {
        $share = $this->receivedShare(
            $recipient,
            $share,
            true
        );

        $this->activityService->record(
            ReportSavedViewShareActivity::ACTION_APPLIED,
            $recipient,
            $share->owner,
            $recipient,
            $share->savedView,
            $share,
            $share->permission,
            $share->permission
        );

        return $share;
    }

    public function receivedShare(
        User $recipient,
        ReportSavedViewShare $share,
        bool $requireUse = false
    ): ReportSavedViewShare {
        abort_unless(
            (int) $share->recipient_user_id
            === (int) $recipient->id,
            404
        );

        $share->loadMissing([
            'owner',
            'savedView',
        ]);

        abort_unless(
            $share->savedView !== null
            && $share->savedView->isActive(),
            404
        );

        if ($requireUse) {
            abort_unless(
                $share->canUse(),
                404
            );
        }

        return $share;
    }

    private function copyName(
        User $recipient,
        string $sourceName
    ): string {
        $base = trim('نسخة من '.$sourceName);
        $candidate = mb_substr(
            $base,
            0,
            120
        );
        $suffix = 2;

        while (
            ReportSavedView::query()
                ->where('user_id', $recipient->id)
                ->where('name', $candidate)
                ->exists()
        ) {
            $suffixText = ' ('.$suffix.')';
            $candidate = mb_substr(
                $base,
                0,
                120 - mb_strlen($suffixText)
            ).$suffixText;
            $suffix++;
        }

        return $candidate;
    }

    private function authorizeOwner(
        User $owner,
        ReportSavedView $savedView
    ): void {
        abort_unless(
            (int) $savedView->user_id
            === (int) $owner->id,
            404
        );
    }

    private function authorizeShareOwner(
        User $owner,
        ReportSavedViewShare $share
    ): void {
        abort_unless(
            (int) $share->owner_user_id
            === (int) $owner->id,
            404
        );

        abort_unless(
            ReportSavedView::query()
                ->whereKey(
                    $share->report_saved_view_id
                )
                ->where(
                    'user_id',
                    $owner->id
                )
                ->exists(),
            404
        );
    }

    private function validatePermission(
        string $permission
    ): string {
        $permission = trim($permission);

        if (
            ! in_array(
                $permission,
                [
                    ReportSavedViewShare::PERMISSION_VIEW,
                    ReportSavedViewShare::PERMISSION_USE,
                ],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'permission' =>
                    'صلاحية المشاركة غير صالحة.',
            ]);
        }

        return $permission;
    }
}
