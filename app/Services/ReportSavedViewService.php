<?php

namespace App\Services;

use App\Models\ReportSavedView;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReportSavedViewService
{
    public function list(User $user, ?string $reportKey = null): Collection
    {
        return ReportSavedView::query()
            ->where('user_id', $user->id)
            ->whereNull('archived_at')
            ->when(
                $reportKey,
                fn (Builder $query) =>
                    $query->where('report_key', $reportKey)
            )
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param array<int, string> $matchingReportKeys
     * @param array<int, string> $matchingFilterValues
     */
    public function paginateForManagement(
        User $user,
        ?string $search = null,
        ?string $reportKey = null,
        array $matchingReportKeys = [],
        array $matchingFilterValues = [],
        int $perPage = 15,
        string $status = 'active'
    ): LengthAwarePaginator {
        $search = trim((string) $search);
        $reportKey = trim((string) $reportKey);
        $perPage = max(5, min($perPage, 100));
        $status = $this->normalizeManagementStatus($status);

        $query = ReportSavedView::query()
            ->where('user_id', $user->id);

        $this->applyManagementStatus($query, $status);

        return $query
            ->when(
                $reportKey !== '',
                fn (Builder $query) =>
                    $query->where('report_key', $reportKey)
            )
            ->when(
                $search !== ''
                    || $matchingReportKeys !== []
                    || $matchingFilterValues !== [],
                function (Builder $query) use (
                    $search,
                    $matchingReportKeys,
                    $matchingFilterValues
                ): void {
                    $query->where(
                        function (Builder $query) use (
                            $search,
                            $matchingReportKeys,
                            $matchingFilterValues
                        ): void {
                            if ($search !== '') {
                                $query
                                    ->where(
                                        'name',
                                        'like',
                                        '%' . $search . '%'
                                    )
                                    ->orWhere(
                                        'report_key',
                                        'like',
                                        '%' . $search . '%'
                                    )
                                    ->orWhere(
                                        'filters',
                                        'like',
                                        '%' . $search . '%'
                                    );
                            }

                            if ($matchingReportKeys !== []) {
                                $query->orWhereIn(
                                    'report_key',
                                    array_values(
                                        array_unique(
                                            $matchingReportKeys
                                        )
                                    )
                                );
                            }

                            foreach (
                                array_values(
                                    array_unique(
                                        $matchingFilterValues
                                    )
                                ) as $filterValue
                            ) {
                                $query->orWhere(
                                    'filters',
                                    'like',
                                    '%' . $filterValue . '%'
                                );
                            }
                        }
                    );
                }
            )
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param array<int, string> $matchingReportKeys
     * @param array<int, string> $matchingFilterValues
     * @return Collection<int, ReportSavedView>
     */
    public function exportForManagement(
        User $user,
        ?string $search = null,
        ?string $reportKey = null,
        array $matchingReportKeys = [],
        array $matchingFilterValues = [],
        string $status = 'active'
    ): Collection {
        $search = trim((string) $search);
        $reportKey = trim((string) $reportKey);
        $status = $this->normalizeManagementStatus($status);

        $query = ReportSavedView::query()
            ->where('user_id', $user->id);

        $this->applyManagementStatus($query, $status);

        return $query
            ->when(
                $reportKey !== '',
                fn (Builder $query) =>
                    $query->where('report_key', $reportKey)
            )
            ->when(
                $search !== ''
                    || $matchingReportKeys !== []
                    || $matchingFilterValues !== [],
                function (Builder $query) use (
                    $search,
                    $matchingReportKeys,
                    $matchingFilterValues
                ): void {
                    $query->where(
                        function (Builder $query) use (
                            $search,
                            $matchingReportKeys,
                            $matchingFilterValues
                        ): void {
                            if ($search !== '') {
                                $query
                                    ->where(
                                        'name',
                                        'like',
                                        '%' . $search . '%'
                                    )
                                    ->orWhere(
                                        'report_key',
                                        'like',
                                        '%' . $search . '%'
                                    )
                                    ->orWhere(
                                        'filters',
                                        'like',
                                        '%' . $search . '%'
                                    );
                            }

                            if ($matchingReportKeys !== []) {
                                $query->orWhereIn(
                                    'report_key',
                                    array_values(
                                        array_unique(
                                            $matchingReportKeys
                                        )
                                    )
                                );
                            }

                            foreach (
                                array_values(
                                    array_unique(
                                        $matchingFilterValues
                                    )
                                ) as $filterValue
                            ) {
                                $query->orWhere(
                                    'filters',
                                    'like',
                                    '%' . $filterValue . '%'
                                );
                            }
                        }
                    );
                }
            )
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param array<int, string> $matchingReportKeys
     * @param array<int, string> $matchingFilterValues
     * @param array<int, int> $tagIds
     */
    public function paginateForManagementByTags(
        User $user,
        ?string $search = null,
        ?string $reportKey = null,
        array $matchingReportKeys = [],
        array $matchingFilterValues = [],
        int $perPage = 15,
        string $status = 'active',
        array $tagIds = []
    ): LengthAwarePaginator {
        $tagIds = $this->ownedManagementTagIds(
            $user,
            $tagIds
        );

        if ($tagIds === []) {
            return $this->paginateForManagement(
                $user,
                $search,
                $reportKey,
                $matchingReportKeys,
                $matchingFilterValues,
                $perPage,
                $status
            );
        }

        $search = trim((string) $search);
        $reportKey = trim((string) $reportKey);
        $perPage = max(5, min($perPage, 100));
        $status = $this->normalizeManagementStatus(
            $status
        );

        $query = ReportSavedView::query()
            ->with('tags')
            ->where('user_id', $user->id);

        $this->applyManagementStatus(
            $query,
            $status
        );
        $this->applyManagementTagFilter(
            $query,
            $tagIds
        );
        $this->applyManagementSearch(
            $query,
            $search,
            $reportKey,
            $matchingReportKeys,
            $matchingFilterValues
        );

        return $query
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param array<int, string> $matchingReportKeys
     * @param array<int, string> $matchingFilterValues
     * @param array<int, int> $tagIds
     * @return Collection<int, ReportSavedView>
     */
    public function exportForManagementByTags(
        User $user,
        ?string $search = null,
        ?string $reportKey = null,
        array $matchingReportKeys = [],
        array $matchingFilterValues = [],
        string $status = 'active',
        array $tagIds = []
    ): Collection {
        $tagIds = $this->ownedManagementTagIds(
            $user,
            $tagIds
        );

        if ($tagIds === []) {
            return $this->exportForManagement(
                $user,
                $search,
                $reportKey,
                $matchingReportKeys,
                $matchingFilterValues,
                $status
            );
        }

        $search = trim((string) $search);
        $reportKey = trim((string) $reportKey);
        $status = $this->normalizeManagementStatus(
            $status
        );

        $query = ReportSavedView::query()
            ->with('tags')
            ->where('user_id', $user->id);

        $this->applyManagementStatus(
            $query,
            $status
        );
        $this->applyManagementTagFilter(
            $query,
            $tagIds
        );
        $this->applyManagementSearch(
            $query,
            $search,
            $reportKey,
            $matchingReportKeys,
            $matchingFilterValues
        );

        return $query
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param array<int, int> $savedViewIds
     * @return Collection<int, ReportSavedView>
     */
    public function exportSelectedForManagement(
        User $user,
        array $savedViewIds
    ): Collection {
        $selectedIds = $this->normalizeIds($savedViewIds);

        if ($selectedIds === []) {
            return collect();
        }

        return ReportSavedView::query()
            ->where('user_id', $user->id)
            ->whereIn('id', $selectedIds)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    public function save(User $user, string $reportKey, string $name, array $filters, bool $isDefault = false): ReportSavedView
    {
        $reportKey = trim($reportKey);
        $name = trim($name);

        if ($reportKey === '') {
            throw new InvalidArgumentException(
                'Report key is required.'
            );
        }

        if ($name === '') {
            throw new InvalidArgumentException(
                'Saved view name is required.'
            );
        }

        return DB::transaction(
            function () use (
                $user,
                $reportKey,
                $name,
                $filters,
                $isDefault
            ): ReportSavedView {
                if ($isDefault) {
                    ReportSavedView::query()
                        ->where('user_id', $user->id)
                        ->where('report_key', $reportKey)
                        ->whereNull('archived_at')
                        ->update(['is_default' => false]);
                }

                return ReportSavedView::query()->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'report_key' => $reportKey,
                        'name' => $name,
                    ],
                    [
                        'filters' => $this->cleanFilters($filters),
                        'is_default' => $isDefault,
                        'archived_at' => null,
                    ]
                );
            }
        );
    }

    public function listForReport(User $user, string $reportKey)
    {
        return ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('report_key', $reportKey)
            ->whereNull('archived_at')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    public function getDefault(User $user, string $reportKey): ?ReportSavedView
    {
        return ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('report_key', $reportKey)
            ->whereNull('archived_at')
            ->where('is_default', true)
            ->first();
    }

    public function archive(
        User $user,
        int $savedViewId
    ): bool {
        return DB::transaction(
            fn (): bool =>
                ReportSavedView::query()
                    ->where('user_id', $user->id)
                    ->whereKey($savedViewId)
                    ->whereNull('archived_at')
                    ->update([
                        'archived_at' => now(),
                        'is_default' => false,
                    ]) > 0
        );
    }

    public function restore(
        User $user,
        int $savedViewId
    ): bool {
        return DB::transaction(
            fn (): bool =>
                ReportSavedView::query()
                    ->where('user_id', $user->id)
                    ->whereKey($savedViewId)
                    ->whereNotNull('archived_at')
                    ->update([
                        'archived_at' => null,
                        'is_default' => false,
                    ]) > 0
        );
    }

    /**
     * @param array<int, int> $savedViewIds
     */
    public function bulkArchive(
        User $user,
        array $savedViewIds
    ): int {
        $selectedIds = $this->normalizeIds($savedViewIds);

        if ($selectedIds === []) {
            return 0;
        }

        return DB::transaction(
            fn (): int =>
                ReportSavedView::query()
                    ->where('user_id', $user->id)
                    ->whereIn('id', $selectedIds)
                    ->whereNull('archived_at')
                    ->update([
                        'archived_at' => now(),
                        'is_default' => false,
                    ])
        );
    }

    /**
     * @param array<int, int> $savedViewIds
     */
    public function bulkRestore(
        User $user,
        array $savedViewIds
    ): int {
        $selectedIds = $this->normalizeIds($savedViewIds);

        if ($selectedIds === []) {
            return 0;
        }

        return DB::transaction(
            fn (): int =>
                ReportSavedView::query()
                    ->where('user_id', $user->id)
                    ->whereIn('id', $selectedIds)
                    ->whereNotNull('archived_at')
                    ->update([
                        'archived_at' => null,
                        'is_default' => false,
                    ])
        );
    }

    public function delete(User $user, int $savedViewId): void
    {
        ReportSavedView::query()
            ->where('user_id', $user->id)
            ->whereKey($savedViewId)
            ->delete();
    }

    public function deleteForReport(User $user, string $reportKey): void
    {
        ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('report_key', $reportKey)
            ->delete();
    }

    private function normalizeManagementStatus(
        ?string $status
    ): string {
        $status = trim((string) $status);

        return in_array(
            $status,
            ['active', 'archived', 'all'],
            true
        )
            ? $status
            : 'active';
    }

    private function applyManagementStatus(
        Builder $query,
        string $status
    ): void {
        if ($status === 'active') {
            $query->whereNull('archived_at');

            return;
        }

        if ($status === 'archived') {
            $query->whereNotNull('archived_at');
        }
    }

    /**
     * @param array<int, string> $matchingReportKeys
     * @param array<int, string> $matchingFilterValues
     */
    private function applyManagementSearch(
        Builder $query,
        string $search,
        string $reportKey,
        array $matchingReportKeys,
        array $matchingFilterValues
    ): void {
        if ($reportKey !== '') {
            $query->where(
                'report_key',
                $reportKey
            );
        }

        if (
            $search === ''
            && $matchingReportKeys === []
            && $matchingFilterValues === []
        ) {
            return;
        }

        $query->where(
            function (Builder $query) use (
                $search,
                $matchingReportKeys,
                $matchingFilterValues
            ): void {
                if ($search !== '') {
                    $query
                        ->where(
                            'name',
                            'like',
                            '%' . $search . '%'
                        )
                        ->orWhere(
                            'report_key',
                            'like',
                            '%' . $search . '%'
                        )
                        ->orWhere(
                            'filters',
                            'like',
                            '%' . $search . '%'
                        );
                }

                if ($matchingReportKeys !== []) {
                    $query->orWhereIn(
                        'report_key',
                        array_values(
                            array_unique(
                                $matchingReportKeys
                            )
                        )
                    );
                }

                foreach (
                    array_values(
                        array_unique(
                            $matchingFilterValues
                        )
                    ) as $filterValue
                ) {
                    $query->orWhere(
                        'filters',
                        'like',
                        '%' . $filterValue . '%'
                    );
                }
            }
        );
    }

    /**
     * @param array<int, int> $tagIds
     */
    private function applyManagementTagFilter(
        Builder $query,
        array $tagIds
    ): void {
        $query->whereHas(
            'tags',
            fn (Builder $tagQuery) =>
                $tagQuery->whereIn(
                    'report_saved_view_tags.id',
                    $tagIds
                )
        );
    }

    /**
     * @param array<int, int> $tagIds
     * @return array<int, int>
     */
    private function ownedManagementTagIds(
        User $user,
        array $tagIds
    ): array {
        $normalizedIds = $this->normalizeIds(
            $tagIds
        );

        if ($normalizedIds === []) {
            return [];
        }

        return DB::table(
            'report_saved_view_tags'
        )
            ->where('user_id', $user->id)
            ->whereIn('id', $normalizedIds)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * @param array<int, int> $savedViewIds
     * @return array<int, int>
     */
    private function normalizeIds(array $savedViewIds): array
    {
        return collect($savedViewIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function cleanFilters(array $filters): array
    {
        $cleaned = [];

        foreach ($filters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (is_array($value)) {
                $value = $this->cleanFilters($value);

                if ($value === []) {
                    continue;
                }
            }

            $cleaned[$key] = $value;
        }

        return $cleaned;
    }
}
