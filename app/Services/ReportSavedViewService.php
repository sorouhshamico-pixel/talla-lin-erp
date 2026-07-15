<?php

namespace App\Services;

use App\Models\ReportSavedView;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReportSavedViewService
{
    public function list(User $user, ?string $reportKey = null): Collection
    {
        return ReportSavedView::query()
            ->where('user_id', $user->id)
            ->when($reportKey, fn ($query) => $query->where('report_key', $reportKey))
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
        int $perPage = 15
    ): LengthAwarePaginator {
        $search = trim((string) $search);
        $reportKey = trim((string) $reportKey);
        $perPage = max(5, min($perPage, 100));

        return ReportSavedView::query()
            ->where('user_id', $user->id)
            ->when($reportKey !== '', fn ($query) => $query->where('report_key', $reportKey))
            ->when(
                $search !== '' || $matchingReportKeys !== [] || $matchingFilterValues !== [],
                function ($query) use ($search, $matchingReportKeys, $matchingFilterValues): void {
                    $query->where(function ($query) use ($search, $matchingReportKeys, $matchingFilterValues): void {
                        if ($search !== '') {
                            $query
                                ->where('name', 'like', '%' . $search . '%')
                                ->orWhere('report_key', 'like', '%' . $search . '%')
                                ->orWhere('filters', 'like', '%' . $search . '%');
                        }

                        if ($matchingReportKeys !== []) {
                            $query->orWhereIn('report_key', array_values(array_unique($matchingReportKeys)));
                        }

                        foreach (array_values(array_unique($matchingFilterValues)) as $filterValue) {
                            $query->orWhere('filters', 'like', '%' . $filterValue . '%');
                        }
                    });
                }
            )
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function save(User $user, string $reportKey, string $name, array $filters, bool $isDefault = false): ReportSavedView
    {
        $reportKey = trim($reportKey);
        $name = trim($name);

        if ($reportKey === '') {
            throw new InvalidArgumentException('Report key is required.');
        }

        if ($name === '') {
            throw new InvalidArgumentException('Saved view name is required.');
        }

        return DB::transaction(function () use ($user, $reportKey, $name, $filters, $isDefault): ReportSavedView {
            if ($isDefault) {
                ReportSavedView::query()
                    ->where('user_id', $user->id)
                    ->where('report_key', $reportKey)
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
                ]
            );
        });
    }

    public function listForReport(User $user, string $reportKey)
    {
        return ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('report_key', $reportKey)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    public function getDefault(User $user, string $reportKey): ?ReportSavedView
    {
        return ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('report_key', $reportKey)
            ->where('is_default', true)
            ->first();
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
