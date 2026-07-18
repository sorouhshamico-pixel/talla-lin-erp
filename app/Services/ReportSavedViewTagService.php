<?php

namespace App\Services;

use App\Models\ReportSavedView;
use App\Models\ReportSavedViewTag;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReportSavedViewTagService
{
    public function listForUser(User $user): Collection
    {
        return ReportSavedViewTag::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    public function create(
        User $user,
        string $name,
        ?string $color = null
    ): ReportSavedViewTag {
        [$displayName, $normalizedName] =
            $this->normalizeName($name);

        return ReportSavedViewTag::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'normalized_name' => $normalizedName,
            ],
            [
                'name' => $displayName,
                'color' => $this->normalizeColor($color),
            ]
        );
    }

    public function update(
        User $user,
        ReportSavedViewTag $tag,
        string $name,
        ?string $color = null
    ): ReportSavedViewTag {
        abort_unless(
            (int) $tag->user_id === (int) $user->id,
            404
        );

        [$displayName, $normalizedName] =
            $this->normalizeName($name);

        $duplicate = ReportSavedViewTag::query()
            ->where('user_id', $user->id)
            ->where('normalized_name', $normalizedName)
            ->whereKeyNot($tag->id)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'name' => 'اسم الوسم مستخدم بالفعل.',
            ]);
        }

        $tag->forceFill([
            'name' => $displayName,
            'normalized_name' => $normalizedName,
            'color' => $this->normalizeColor($color),
        ])->save();

        return $tag->refresh();
    }

    public function delete(
        User $user,
        ReportSavedViewTag $tag
    ): void {
        abort_unless(
            (int) $tag->user_id === (int) $user->id,
            404
        );

        $tag->delete();
    }

    public function syncSavedViewTags(
        User $user,
        ReportSavedView $savedView,
        array $tagIds
    ): void {
        abort_unless(
            (int) $savedView->user_id === (int) $user->id,
            404
        );

        $ownedTagIds = $this->ownedTagIds(
            $user,
            $tagIds
        );

        DB::transaction(
            fn () => $savedView->tags()->sync(
                $ownedTagIds
            )
        );
    }

    public function bulkAttach(
        User $user,
        array $savedViewIds,
        array $tagIds
    ): int {
        $savedViews = $this->ownedSavedViews(
            $user,
            $savedViewIds
        );
        $ownedTagIds = $this->ownedTagIds(
            $user,
            $tagIds
        );

        if (
            $savedViews->isEmpty()
            || $ownedTagIds === []
        ) {
            return 0;
        }

        DB::transaction(
            function () use (
                $savedViews,
                $ownedTagIds
            ): void {
                foreach ($savedViews as $savedView) {
                    $savedView->tags()
                        ->syncWithoutDetaching(
                            $ownedTagIds
                        );
                }
            }
        );

        return $savedViews->count();
    }

    public function bulkDetach(
        User $user,
        array $savedViewIds,
        array $tagIds
    ): int {
        $savedViews = $this->ownedSavedViews(
            $user,
            $savedViewIds
        );
        $ownedTagIds = $this->ownedTagIds(
            $user,
            $tagIds
        );

        if (
            $savedViews->isEmpty()
            || $ownedTagIds === []
        ) {
            return 0;
        }

        DB::transaction(
            function () use (
                $savedViews,
                $ownedTagIds
            ): void {
                foreach ($savedViews as $savedView) {
                    $savedView->tags()->detach(
                        $ownedTagIds
                    );
                }
            }
        );

        return $savedViews->count();
    }

    private function normalizeName(
        string $name
    ): array {
        $displayName = preg_replace(
            '/\s+/u',
            ' ',
            trim($name)
        ) ?? '';

        if (
            $displayName === ''
            || mb_strlen($displayName) > 40
        ) {
            throw ValidationException::withMessages([
                'name' =>
                    'اسم الوسم مطلوب وبحد أقصى 40 حرفًا.',
            ]);
        }

        return [
            $displayName,
            mb_strtolower(
                $displayName,
                'UTF-8'
            ),
        ];
    }

    private function normalizeColor(
        ?string $color
    ): ?string {
        $color = trim((string) $color);

        if ($color === '') {
            return null;
        }

        if (
            ! preg_match(
                '/^#[0-9A-Fa-f]{6}$/',
                $color
            )
        ) {
            throw ValidationException::withMessages([
                'color' =>
                    'لون الوسم يجب أن يكون بصيغة #RRGGBB.',
            ]);
        }

        return strtoupper($color);
    }

    private function normalizeIds(array $ids): array
    {
        return collect($ids)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function ownedTagIds(
        User $user,
        array $tagIds
    ): array {
        return ReportSavedViewTag::query()
            ->where('user_id', $user->id)
            ->whereIn(
                'id',
                $this->normalizeIds($tagIds)
            )
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    private function ownedSavedViews(
        User $user,
        array $savedViewIds
    ): Collection {
        return ReportSavedView::query()
            ->where('user_id', $user->id)
            ->whereIn(
                'id',
                $this->normalizeIds(
                    $savedViewIds
                )
            )
            ->get();
    }
}
