<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\PartyContactLog;
use App\Models\PartyTag;
use App\Models\Supplier;
use Illuminate\Support\Facades\Schema;

class PartyDashboardSummaryService
{
    public function summary(): array
    {
        return [
            'customers_total' => $this->modelCount(Customer::class),
            'customers_active' => $this->activeCount(Customer::class, 'customers'),

            'suppliers_total' => $this->modelCount(Supplier::class),
            'suppliers_active' => $this->activeCount(Supplier::class, 'suppliers'),

            'follow_ups_due' => $this->dueFollowUpsCount(),
            'follow_ups_upcoming' => $this->upcomingFollowUpsCount(),
            'follow_ups_completed' => $this->completedFollowUpsCount(),

            'party_tags_total' => $this->partyTagsCount(),
            'duplicate_groups_total' => app(PartyDuplicateService::class)->totalDuplicateGroups(),
        ];
    }

    private function modelCount(string $modelClass): int
    {
        return (int) $modelClass::query()->count();
    }

    private function activeCount(string $modelClass, string $table): int
    {
        if (Schema::hasColumn($table, 'is_active')) {
            return (int) $modelClass::query()->where('is_active', true)->count();
        }

        if (Schema::hasColumn($table, 'active')) {
            return (int) $modelClass::query()->where('active', true)->count();
        }

        return (int) $modelClass::query()->count();
    }

    private function dueFollowUpsCount(): int
    {
        if (! Schema::hasTable('party_contact_logs')) {
            return 0;
        }

        $query = PartyContactLog::query()
            ->whereNotNull('follow_up_at')
            ->whereDate('follow_up_at', '<=', now()->toDateString());

        if (Schema::hasColumn('party_contact_logs', 'follow_up_completed_at')) {
            $query->whereNull('follow_up_completed_at');
        }

        return (int) $query->count();
    }

    private function upcomingFollowUpsCount(): int
    {
        if (! Schema::hasTable('party_contact_logs')) {
            return 0;
        }

        $query = PartyContactLog::query()
            ->whereNotNull('follow_up_at')
            ->whereDate('follow_up_at', '>', now()->toDateString());

        if (Schema::hasColumn('party_contact_logs', 'follow_up_completed_at')) {
            $query->whereNull('follow_up_completed_at');
        }

        return (int) $query->count();
    }

    private function completedFollowUpsCount(): int
    {
        if (! Schema::hasTable('party_contact_logs')) {
            return 0;
        }

        if (! Schema::hasColumn('party_contact_logs', 'follow_up_completed_at')) {
            return 0;
        }

        return (int) PartyContactLog::query()
            ->whereNotNull('follow_up_at')
            ->whereNotNull('follow_up_completed_at')
            ->count();
    }

    private function partyTagsCount(): int
    {
        if (! Schema::hasTable('party_tags')) {
            return 0;
        }

        return (int) PartyTag::query()->count();
    }
}
