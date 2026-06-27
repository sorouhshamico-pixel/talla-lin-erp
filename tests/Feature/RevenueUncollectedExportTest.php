<?php

namespace Tests\Feature;

use App\Models\Revenue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RevenueUncollectedExportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private array $createdRelatedRecords = [];

    public function test_uncollected_revenues_can_be_exported_to_csv(): void
    {
        $this->user = User::factory()->create();

        $this->actingAs($this->user);

        $uncollectedId = $this->insertRevenueForExportTest(
            label: 'export-uncollected-12i-' . uniqid(),
            uncollected: true
        );

        $collectedId = $this->insertRevenueForExportTest(
            label: 'export-collected-12i-' . uniqid(),
            uncollected: false
        );

        $response = $this->get(route('revenues.uncollected.export'));

        $response->assertOk();

        $contentDisposition = $response->headers->get('content-disposition');

        $this->assertNotNull($contentDisposition);
        $this->assertStringContainsString('uncollected-revenues-', $contentDisposition);
        $this->assertStringContainsString('.csv', $contentDisposition);

        $csv = $response->streamedContent();
        $rows = $this->parseCsv($csv);

        $this->assertNotEmpty($rows);

        $header = $rows[0];
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);

        $idIndex = array_search('id', $header, true);

        $this->assertNotFalse($idIndex, 'CSV header must contain id column.');

        $exportedIds = collect(array_slice($rows, 1))
            ->map(fn (array $row) => $row[$idIndex] ?? null)
            ->filter()
            ->values()
            ->all();

        $this->assertContains((string) $uncollectedId, $exportedIds);
        $this->assertNotContains((string) $collectedId, $exportedIds);
    }

    private function insertRevenueForExportTest(string $label, bool $uncollected): int
    {
        $row = array_merge(
            $this->baseRow($this->revenueTable(), $label),
            $uncollected
                ? $this->uncollectedMarkerAttributes()
                : $this->collectedMarkerAttributes()
        );

        return (int) DB::table($this->revenueTable())->insertGetId($row);
    }

    private function baseRow(string $table, ?string $label = null, int $depth = 0): array
    {
        $row = [];
        $now = now();

        foreach ($this->tableColumns($table) as $column) {
            $name = $column['name'];
            $type = strtolower($column['type'] ?? '');

            if ($this->isAutoIncrementingPrimaryKey($column)) {
                continue;
            }

            if ($name === 'deleted_at') {
                continue;
            }

            if ($name === 'created_at' || $name === 'updated_at') {
                $row[$name] = $now;
                continue;
            }

            if ($table === $this->revenueTable() && $label !== null) {
                $labelColumn = $this->labelColumn();

                if ($labelColumn !== null) {
                    $row[$labelColumn] = $label;
                }
            }

            if (array_key_exists($name, $row)) {
                continue;
            }

            if ($this->columnIsNullableOrHasDefault($column)) {
                continue;
            }

            $row[$name] = $this->fallbackValueForRequiredColumn($table, $name, $type, $depth);
        }

        return $row;
    }

    private function fallbackValueForRequiredColumn(string $table, string $name, string $type, int $depth): mixed
    {
        if ($this->isUserReferenceColumn($name)) {
            return $this->user->id;
        }

        if (str_ends_with($name, '_id')) {
            return $this->relatedIdForColumn($table, $name, $depth + 1);
        }

        if (str_contains($name, 'email')) {
            return 'test-' . uniqid() . '@example.com';
        }

        if (str_contains($name, 'phone') || str_contains($name, 'mobile')) {
            return '0500000000';
        }

        if (str_contains($name, 'slug') || str_contains($name, 'code') || str_contains($name, 'number')) {
            return 'test-' . uniqid();
        }

        if (str_contains($name, 'name') || str_contains($name, 'title')) {
            return 'Test ' . $table . ' ' . uniqid();
        }

        if (str_contains($name, 'status')) {
            return $this->firstEnumValueFromType($type) ?? 'active';
        }

        if (str_contains($type, 'bool') || str_contains($type, 'tinyint(1)')) {
            return true;
        }

        if (
            str_contains($type, 'int')
            || str_contains($type, 'decimal')
            || str_contains($type, 'double')
            || str_contains($type, 'float')
            || str_contains($type, 'real')
            || str_contains($type, 'numeric')
        ) {
            return 1;
        }

        if (str_contains($type, 'date') || str_contains($type, 'time')) {
            return now();
        }

        if (str_contains($type, 'json')) {
            return json_encode([]);
        }

        return $this->firstEnumValueFromType($type) ?? 'Test value';
    }

    private function relatedIdForColumn(string $sourceTable, string $column, int $depth): int
    {
        if ($depth > 5) {
            return 1;
        }

        $relatedTable = $this->relatedTableForColumn($sourceTable, $column);

        if ($relatedTable === null || ! Schema::hasTable($relatedTable)) {
            return 1;
        }

        $existingId = DB::table($relatedTable)->value('id');

        if ($existingId !== null) {
            return (int) $existingId;
        }

        if (isset($this->createdRelatedRecords[$relatedTable])) {
            return (int) $this->createdRelatedRecords[$relatedTable];
        }

        $row = $this->baseRow($relatedTable, null, $depth);

        $id = (int) DB::table($relatedTable)->insertGetId($row);

        $this->createdRelatedRecords[$relatedTable] = $id;

        return $id;
    }

    private function relatedTableForColumn(string $sourceTable, string $column): ?string
    {
        $foreignTable = $this->foreignTableForColumn($sourceTable, $column);

        if ($foreignTable !== null) {
            return $foreignTable;
        }

        return match ($column) {
            'company_id' => 'companies',
            'branch_id' => 'branches',
            'customer_id' => 'customers',
            'client_id' => 'clients',
            'project_id' => 'projects',
            'invoice_id' => 'invoices',
            default => str($column)->beforeLast('_id')->plural()->toString(),
        };
    }

    private function foreignTableForColumn(string $sourceTable, string $column): ?string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $foreignKeys = DB::select("PRAGMA foreign_key_list('{$sourceTable}')");

            foreach ($foreignKeys as $foreignKey) {
                if (($foreignKey->from ?? null) === $column) {
                    return $foreignKey->table ?? null;
                }
            }
        }

        return null;
    }

    private function isUserReferenceColumn(string $name): bool
    {
        return in_array($name, [
            'user_id',
            'owner_id',
            'owner_user_id',
            'created_by',
            'updated_by',
            'deleted_by',
            'approved_by',
            'received_by',
            'collected_by',
        ], true);
    }

    private function uncollectedMarkerAttributes(): array
    {
        if ($this->hasRevenueColumn('is_collected')) {
            return ['is_collected' => false];
        }

        if ($this->hasRevenueColumn('collected_at')) {
            return ['collected_at' => null];
        }

        foreach (['remaining_amount', 'balance', 'due_amount', 'uncollected_amount'] as $remainingColumn) {
            if ($this->hasRevenueColumn($remainingColumn)) {
                return [$remainingColumn => 500];
            }
        }

        foreach ($this->amountPairs() as [$amountColumn, $collectedColumn]) {
            if ($this->hasRevenueColumn($amountColumn) && $this->hasRevenueColumn($collectedColumn)) {
                return [
                    $amountColumn => 1000,
                    $collectedColumn => 250,
                ];
            }
        }

        foreach (['collection_status', 'payment_status', 'status'] as $statusColumn) {
            if ($this->hasRevenueColumn($statusColumn)) {
                return [$statusColumn => $this->statusValue($statusColumn, true)];
            }
        }

        $this->fail('No supported uncollected revenue marker column was found.');
    }

    private function collectedMarkerAttributes(): array
    {
        if ($this->hasRevenueColumn('is_collected')) {
            return ['is_collected' => true];
        }

        if ($this->hasRevenueColumn('collected_at')) {
            return ['collected_at' => now()];
        }

        foreach (['remaining_amount', 'balance', 'due_amount', 'uncollected_amount'] as $remainingColumn) {
            if ($this->hasRevenueColumn($remainingColumn)) {
                return [$remainingColumn => 0];
            }
        }

        foreach ($this->amountPairs() as [$amountColumn, $collectedColumn]) {
            if ($this->hasRevenueColumn($amountColumn) && $this->hasRevenueColumn($collectedColumn)) {
                return [
                    $amountColumn => 1000,
                    $collectedColumn => 1000,
                ];
            }
        }

        foreach (['collection_status', 'payment_status', 'status'] as $statusColumn) {
            if ($this->hasRevenueColumn($statusColumn)) {
                return [$statusColumn => $this->statusValue($statusColumn, false)];
            }
        }

        $this->fail('No supported collected revenue marker column was found.');
    }

    private function statusValue(string $column, bool $uncollected): string
    {
        $values = $this->enumValues($this->revenueTable(), $column);

        $uncollectedOptions = [
            'uncollected',
            'unpaid',
            'pending',
            'partial',
            'partially_paid',
            'overdue',
            'due',
            'not_collected',
            'not_paid',
            'غير محصل',
            'غير محصلة',
            'غير مدفوع',
            'جزئي',
        ];

        $collectedOptions = [
            'collected',
            'paid',
            'completed',
            'received',
            'محصل',
            'محصلة',
            'مدفوع',
        ];

        $options = $uncollected ? $uncollectedOptions : $collectedOptions;

        if ($values === []) {
            return $options[0];
        }

        foreach ($options as $option) {
            if (in_array($option, $values, true)) {
                return $option;
            }
        }

        return $values[0];
    }

    private function amountPairs(): array
    {
        return [
            ['amount', 'collected_amount'],
            ['amount', 'paid_amount'],
            ['amount', 'received_amount'],
            ['total_amount', 'collected_amount'],
            ['total_amount', 'paid_amount'],
            ['total_amount', 'received_amount'],
            ['invoice_amount', 'collected_amount'],
            ['invoice_amount', 'paid_amount'],
            ['invoice_amount', 'received_amount'],
        ];
    }

    private function labelColumn(): ?string
    {
        foreach ([
            'client_name',
            'customer_name',
            'customer',
            'name',
            'title',
            'description',
            'notes',
            'source',
            'reference',
        ] as $column) {
            if ($this->hasRevenueColumn($column)) {
                return $column;
            }
        }

        return null;
    }

    private function parseCsv(string $csv): array
    {
        $handle = fopen('php://temp', 'r+');

        fwrite($handle, $csv);
        rewind($handle);

        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function revenueTable(): string
    {
        return (new Revenue())->getTable();
    }

    private function hasRevenueColumn(string $column): bool
    {
        return Schema::hasColumn($this->revenueTable(), $column);
    }

    private function tableColumns(string $table): array
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return collect(DB::select("PRAGMA table_info('{$table}')"))
                ->map(fn ($column) => [
                    'name' => $column->name,
                    'type' => $column->type,
                    'nullable' => ! (bool) $column->notnull,
                    'default' => $column->dflt_value,
                    'primary' => (bool) $column->pk,
                    'extra' => '',
                ])
                ->all();
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            return collect(DB::select('SHOW COLUMNS FROM ' . $table))
                ->map(fn ($column) => [
                    'name' => $column->Field,
                    'type' => $column->Type,
                    'nullable' => $column->Null === 'YES',
                    'default' => $column->Default,
                    'primary' => $column->Key === 'PRI',
                    'extra' => $column->Extra,
                ])
                ->all();
        }

        return collect(Schema::getColumnListing($table))
            ->map(fn (string $column) => [
                'name' => $column,
                'type' => 'string',
                'nullable' => true,
                'default' => null,
                'primary' => $column === 'id',
                'extra' => '',
            ])
            ->all();
    }

    private function columnIsNullableOrHasDefault(array $column): bool
    {
        return $column['nullable']
            || $column['default'] !== null
            || str_contains(strtolower($column['extra'] ?? ''), 'auto_increment');
    }

    private function isAutoIncrementingPrimaryKey(array $column): bool
    {
        return ($column['primary'] ?? false) && $column['name'] === 'id';
    }

    private function enumValues(string $table, string $column): array
    {
        foreach ($this->tableColumns($table) as $tableColumn) {
            if ($tableColumn['name'] === $column) {
                return $this->enumValuesFromType(strtolower($tableColumn['type']));
            }
        }

        return [];
    }

    private function firstEnumValueFromType(string $type): ?string
    {
        $values = $this->enumValuesFromType($type);

        return $values[0] ?? null;
    }

    private function enumValuesFromType(string $type): array
    {
        if (! str_starts_with($type, 'enum(')) {
            return [];
        }

        preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $type, $matches);

        return array_map(
            static fn ($value) => str_replace("\\'", "'", $value),
            $matches[1] ?? []
        );
    }
}
