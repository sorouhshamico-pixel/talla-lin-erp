<?php

namespace App\Services;

use App\Models\ReportSavedView;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ReportSavedViewImportApplyService
{
    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array{created: int, skipped: int}
     */
    public function apply(User $user, array $rows): array
    {
        return DB::transaction(function () use ($user, $rows): array {
            $created = 0;
            $skipped = 0;

            foreach ($rows as $row) {
                if (($row['status'] ?? '') !== 'valid') {
                    continue;
                }

                $exists = ReportSavedView::query()
                    ->where('user_id', $user->id)
                    ->where('report_key', $row['report_key'])
                    ->where('name', $row['name'])
                    ->exists();

                if ($exists) {
                    $skipped++;

                    continue;
                }

                $isDefault = ($row['is_default'] ?? '') === 'نعم';

                if ($isDefault) {
                    ReportSavedView::query()
                        ->where('user_id', $user->id)
                        ->where('report_key', $row['report_key'])
                        ->update(['is_default' => false]);
                }

                ReportSavedView::query()->create([
                    'user_id' => $user->id,
                    'report_key' => $row['report_key'],
                    'name' => $row['name'],
                    'filters' => $row['filters'] ?? [],
                    'is_default' => $isDefault,
                ]);

                $created++;
            }

            return [
                'created' => $created,
                'skipped' => $skipped,
            ];
        });
    }
}
