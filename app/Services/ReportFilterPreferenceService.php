<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserReportFilterPreference;

class ReportFilterPreferenceService
{
    public function get(User $user, string $reportKey): array
    {
        $preference = UserReportFilterPreference::query()
            ->where('user_id', $user->id)
            ->where('report_key', $reportKey)
            ->first();

        return $preference?->filters ?? [];
    }

    public function save(User $user, string $reportKey, array $filters): UserReportFilterPreference
    {
        return UserReportFilterPreference::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'report_key' => $reportKey,
            ],
            [
                'filters' => $this->normalizeFilters($filters),
            ]
        );
    }

    public function clear(User $user, string $reportKey): void
    {
        UserReportFilterPreference::query()
            ->where('user_id', $user->id)
            ->where('report_key', $reportKey)
            ->delete();
    }

    public function merge(User $user, string $reportKey, array $requestFilters): array
    {
        return array_merge(
            $this->get($user, $reportKey),
            $this->normalizeFilters($requestFilters)
        );
    }

    private function normalizeFilters(array $filters): array
    {
        return collect($filters)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();
    }
}
