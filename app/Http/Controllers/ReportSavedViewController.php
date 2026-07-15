<?php

namespace App\Http\Controllers;

use App\Models\ReportSavedView;
use App\Services\ReportSavedViewService;
use App\Support\Reports\ReportSavedViewRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportSavedViewController extends Controller
{
    private const FILTER_LABELS = [
        'branch_id' => 'الفرع',
        'customer_id' => 'العميل',
        'supplier_id' => 'المورد',
        'date_from' => 'من تاريخ',
        'date_to' => 'إلى تاريخ',
        'as_of_date' => 'تاريخ التقرير',
        'payment_status' => 'حالة الدفع',
        'aging_bucket' => 'شريحة العمر',
    ];

    private const PAYMENT_STATUS_LABELS = [
        'unpaid' => 'غير مدفوعة',
        'partial' => 'مدفوعة جزئيًا',
        'paid' => 'مدفوعة بالكامل',
    ];

    private const AGING_BUCKET_LABELS = [
        'not_due' => 'غير مستحقة بعد',
        'overdue_1_30' => 'متأخرة 1 إلى 30 يوم',
        'overdue_31_60' => 'متأخرة 31 إلى 60 يوم',
        'overdue_61_90' => 'متأخرة 61 إلى 90 يوم',
        'overdue_more_than_90' => 'أكثر من 90 يوم',
        'without_due_date' => 'بدون تاريخ استحقاق',
    ];

    private const IMPORT_PREVIEW_REQUIRED_COLUMNS = [
        'name',
        'report_label',
        'report_key',
        'is_default',
        'filter_count',
        'filters_summary',
        'updated_at',
    ];

    public function index(Request $request, ReportSavedViewService $savedViewService): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'report_key' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $reportKey = trim((string) ($validated['report_key'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 15);

        if ($reportKey !== '' && ! ReportSavedViewRegistry::has($reportKey)) {
            $reportKey = '';
        }

        $savedViews = $savedViewService->paginateForManagement(
            $request->user(),
            $search,
            $reportKey,
            $this->matchingReportKeysForSearch($search),
            $this->matchingFilterValuesForSearch($search),
            $perPage
        );

        $savedViews->getCollection()->transform(
            fn (ReportSavedView $savedView) => $this->formatSavedView($savedView)
        );

        return view('reports.saved-views.index', [
            'savedViews' => $savedViews,
            'totalSavedViews' => $savedViews->total(),
            'filters' => [
                'search' => $search,
                'report_key' => $reportKey,
                'per_page' => $savedViews->perPage(),
            ],
            'reportOptions' => $this->reportFilterOptions(),
            'importPreview' => null,
        ]);
    }

    public function previewImport(Request $request, ReportSavedViewService $savedViewService): View
    {
        $validated = $request->validate([
            'csv_file' => ['required', 'file', 'max:2048'],
            'search' => ['nullable', 'string', 'max:120'],
            'report_key' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $reportKey = trim((string) ($validated['report_key'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 15);

        if ($reportKey !== '' && ! ReportSavedViewRegistry::has($reportKey)) {
            $reportKey = '';
        }

        $savedViews = $savedViewService->paginateForManagement(
            $request->user(),
            $search,
            $reportKey,
            $this->matchingReportKeysForSearch($search),
            $this->matchingFilterValuesForSearch($search),
            $perPage
        );

        $savedViews->getCollection()->transform(
            fn (ReportSavedView $savedView) => $this->formatSavedView($savedView)
        );

        $csvFile = $request->file('csv_file');
        $csvPath = (string) $csvFile->getRealPath();
        $importPreview = $this->previewSavedViewImport($csvPath);
        $importPreview['csv_payload'] = base64_encode((string) file_get_contents($csvPath));

        return view('reports.saved-views.index', [
            'savedViews' => $savedViews,
            'totalSavedViews' => $savedViews->total(),
            'filters' => [
                'search' => $search,
                'report_key' => $reportKey,
                'per_page' => $savedViews->perPage(),
            ],
            'reportOptions' => $this->reportFilterOptions(),
            'importPreview' => $importPreview,
        ]);
    }

    public function applyImport(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'csv_payload' => ['required', 'string'],
        ]);

        $payload = base64_decode((string) $validated['csv_payload'], true);

        if ($payload === false || $payload === '') {
            return redirect()
                ->route('reports.saved-views.index')
                ->with('status', 'تعذر قراءة ملف الاستيراد.');
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'saved-view-import-');

        if ($tempPath === false) {
            return redirect()
                ->route('reports.saved-views.index')
                ->with('status', 'تعذر تجهيز ملف الاستيراد.');
        }

        file_put_contents($tempPath, $payload);
        $preview = $this->previewSavedViewImport($tempPath);
        @unlink($tempPath);

        if ($preview['header_errors'] !== [] || $preview['invalid_rows'] > 0) {
            return redirect()
                ->route('reports.saved-views.index')
                ->with('status', 'لم يتم تطبيق الاستيراد بسبب وجود أخطاء في الملف.');
        }

        $result = $this->applySavedViewImportRows($request, $preview['rows']);

        return redirect()
            ->route('reports.saved-views.index')
            ->with(
                'status',
                'تم تطبيق الاستيراد: تم إنشاء ' . $result['created'] . ' عرض محفوظ، وتم تخطي ' . $result['skipped'] . ' مكرر.'
            );
    }

    public function export(Request $request, ReportSavedViewService $savedViewService): StreamedResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'report_key' => ['nullable', 'string', 'max:120'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $reportKey = trim((string) ($validated['report_key'] ?? ''));

        if ($reportKey !== '' && ! ReportSavedViewRegistry::has($reportKey)) {
            $reportKey = '';
        }

        $savedViews = $savedViewService->exportForManagement(
            $request->user(),
            $search,
            $reportKey,
            $this->matchingReportKeysForSearch($search),
            $this->matchingFilterValuesForSearch($search)
        );

        $fileName = 'saved-views-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($savedViews): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'name',
                'report_label',
                'report_key',
                'is_default',
                'filter_count',
                'filters_summary',
                'updated_at',
            ]);

            foreach ($savedViews as $savedView) {
                $formatted = $this->formatSavedView($savedView);
                $filtersSummary = $formatted->filters
                    ->map(function (array $filter): string {
                        $displayValue = (string) ($filter['display_value'] ?? '');
                        $rawValue = (string) ($filter['value'] ?? '');

                        if ($rawValue !== '' && $rawValue !== $displayValue) {
                            return $filter['label'] . ': ' . $displayValue . ' (' . $rawValue . ')';
                        }

                        return $filter['label'] . ': ' . $displayValue;
                    })
                    ->implode('; ');

                fputcsv($handle, [
                    $formatted->name,
                    $formatted->report_label,
                    $formatted->report_key,
                    $formatted->is_default ? 'yes' : 'no',
                    $formatted->filters->count(),
                    $filtersSummary,
                    optional($formatted->updated_at)->toDateTimeString() ?? '',
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function makeDefault(Request $request, ReportSavedView $savedView): RedirectResponse
    {
        $this->authorizeSavedView($request, $savedView);

        ReportSavedView::query()
            ->where('user_id', $request->user()->id)
            ->where('report_key', $savedView->report_key)
            ->update(['is_default' => false]);

        $savedView->forceFill(['is_default' => true])->save();

        return redirect()
            ->route('reports.saved-views.index')
            ->with('status', 'تم تعيين العرض الافتراضي للتقرير.');
    }




    public function apply(Request $request, ReportSavedView $savedView): RedirectResponse
    {
        $this->authorizeSavedView($request, $savedView);

        $filters = array_merge($savedView->filters ?? [], [
            'saved_view_id' => $savedView->id,
        ]);

        $reportUrl = $this->reportUrl($savedView->report_key, $filters);

        if ($reportUrl === null) {
            return redirect()
                ->route('reports.saved-views.index')
                ->with('status', 'لا يمكن تطبيق هذا العرض لأن مسار التقرير غير معروف.');
        }

        return redirect()->to($reportUrl);
    }

    public function duplicate(Request $request, ReportSavedView $savedView): RedirectResponse
    {
        $this->authorizeSavedView($request, $savedView);

        $name = mb_substr($savedView->name . ' - نسخة', 0, 120);

        ReportSavedView::query()->create([
            'user_id' => $request->user()->id,
            'report_key' => $savedView->report_key,
            'name' => $name,
            'filters' => $savedView->filters ?? [],
            'is_default' => false,
        ]);

        return redirect()
            ->route('reports.saved-views.index')
            ->with('status', 'تم نسخ العرض المحفوظ بنجاح.');
    }

    public function edit(Request $request, ReportSavedView $savedView): View
    {
        $this->authorizeSavedView($request, $savedView);

        $filters = $savedView->filters ?? [];

        return view('reports.saved-views.edit', [
            'savedView' => $savedView,
            'reportName' => $this->formatReportName($savedView->report_key),
            'filters' => collect($filters)
                ->map(fn ($value, $key) => [
                    'key' => $key,
                    'label' => $this->formatFilterKey((string) $key),
                    'value' => $this->formatFilterDisplayValue((string) $key, $value),
                    'raw_value' => $this->formatFilterValue($value),
                ])
                ->values(),
        ]);
    }

    public function update(Request $request, ReportSavedView $savedView): RedirectResponse
    {
        $this->authorizeSavedView($request, $savedView);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'is_default' => ['nullable'],
        ]);

        DB::transaction(function () use ($request, $savedView, $validated): void {
            $isDefault = $request->boolean('is_default');


            if ($isDefault) {
                ReportSavedView::query()
                    ->where('user_id', $request->user()->id)
                    ->where('report_key', $savedView->report_key)
                    ->where('id', '!=', $savedView->id)
                    ->update(['is_default' => false]);
            }

            $savedView->forceFill([
                'name' => $validated['name'],
                'is_default' => $isDefault,
            ])->save();
        });

        return redirect()
            ->route('reports.saved-views.index')
            ->with('status', 'تم تحديث العرض المحفوظ بنجاح.');
    }

    public function destroy(Request $request, ReportSavedView $savedView, ReportSavedViewService $savedViewService): RedirectResponse
    {
        $this->authorizeSavedView($request, $savedView);

        $savedViewService->delete($request->user(), $savedView->id);

        return redirect()
            ->route('reports.saved-views.index')
            ->with('status', 'تم حذف العرض المحفوظ.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'saved_view_ids' => ['required', 'array', 'min:1'],
            'saved_view_ids.*' => ['integer', 'distinct'],
            'return_search' => ['nullable', 'string', 'max:120'],
            'return_report_key' => ['nullable', 'string', 'max:120'],
            'return_per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
            'return_page' => ['nullable', 'integer', 'min:1'],
        ]);

        $selectedIds = collect($validated['saved_view_ids'])
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        $deletedCount = ReportSavedView::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('id', $selectedIds)
            ->delete();

        return redirect()
            ->route('reports.saved-views.index', $this->managementReturnQuery($request))
            ->with(
                'status',
                $deletedCount > 0
                    ? 'تم حذف ' . $deletedCount . ' من العروض المحددة.'
                    : 'لم يتم حذف أي عروض محفوظة.'
            );
    }

    public function destroyAll(Request $request): RedirectResponse
    {
        ReportSavedView::query()
            ->where('user_id', $request->user()->id)
            ->delete();

        return redirect()
            ->route('reports.saved-views.index')
            ->with('status', 'تم حذف جميع العروض المحفوظة.');
    }

    /**
     * @return array<string, mixed>
     */
    private function managementReturnQuery(Request $request): array
    {
        $search = trim((string) $request->input('return_search', ''));
        $reportKey = trim((string) $request->input('return_report_key', ''));
        $perPage = $request->input('return_per_page');
        $page = $request->input('return_page');

        if ($reportKey !== '' && ! ReportSavedViewRegistry::has($reportKey)) {
            $reportKey = '';
        }

        $query = [];

        if ($search !== '') {
            $query['search'] = $search;
        }

        if ($reportKey !== '') {
            $query['report_key'] = $reportKey;
        }

        if ($perPage !== null && $perPage !== '') {
            $perPage = (int) $perPage;

            if ($perPage >= 5 && $perPage <= 100) {
                $query['per_page'] = $perPage;
            }
        }

        if ($page !== null && $page !== '') {
            $page = (int) $page;

            if ($page > 1) {
                $query['page'] = $page;
            }
        }

        return $query;
    }

    /**
     * @return array{
     *     headers: array<int, string>,
     *     header_errors: array<int, string>,
     *     rows: array<int, array<string, mixed>>,
     *     total_rows: int,
     *     valid_rows: int,
     *     invalid_rows: int
     * }
     */
    private function previewSavedViewImport(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return [
                'headers' => [],
                'header_errors' => ['تعذر قراءة ملف CSV.'],
                'rows' => [],
                'total_rows' => 0,
                'valid_rows' => 0,
                'invalid_rows' => 0,
            ];
        }

        $headers = fgetcsv($handle);

        if (! is_array($headers)) {
            fclose($handle);

            return [
                'headers' => [],
                'header_errors' => ['ملف CSV فارغ أو غير صالح.'],
                'rows' => [],
                'total_rows' => 0,
                'valid_rows' => 0,
                'invalid_rows' => 0,
            ];
        }

        $headers = array_map(
            fn ($header): string => trim(str_replace("\xEF\xBB\xBF", '', (string) $header)),
            $headers
        );

        $missingColumns = array_values(array_diff(self::IMPORT_PREVIEW_REQUIRED_COLUMNS, $headers));
        $rows = [];
        $rowNumber = 1;

        if ($missingColumns !== []) {
            fclose($handle);

            return [
                'headers' => $headers,
                'header_errors' => ['الأعمدة المطلوبة غير موجودة: ' . implode(', ', $missingColumns)],
                'rows' => [],
                'total_rows' => 0,
                'valid_rows' => 0,
                'invalid_rows' => 0,
            ];
        }

        $indexes = array_flip($headers);

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if ($this->isEmptyCsvRow($row)) {
                continue;
            }

            $data = [];

            foreach (self::IMPORT_PREVIEW_REQUIRED_COLUMNS as $column) {
                $data[$column] = trim((string) ($row[$indexes[$column]] ?? ''));
            }

            $errors = [];
            $name = $data['name'];
            $reportKey = $data['report_key'];
            $filterCount = $data['filter_count'];
            $isDefault = mb_strtolower($data['is_default'], 'UTF-8');

            if ($name === '') {
                $errors[] = 'اسم العرض مطلوب.';
            } elseif (mb_strlen($name, 'UTF-8') > 120) {
                $errors[] = 'اسم العرض يتجاوز 120 حرفًا.';
            }

            if ($reportKey === '') {
                $errors[] = 'مفتاح التقرير مطلوب.';
            } elseif (! ReportSavedViewRegistry::has($reportKey)) {
                $errors[] = 'مفتاح التقرير غير معروف.';
            }

            if ($isDefault !== '' && ! in_array($isDefault, ['yes', 'no', '1', '0', 'true', 'false', 'نعم', 'لا'], true)) {
                $errors[] = 'قيمة الافتراضي غير صالحة.';
            }

            if ($filterCount !== '' && (! ctype_digit($filterCount) || (int) $filterCount < 0)) {
                $errors[] = 'عدد الفلاتر يجب أن يكون رقمًا صحيحًا.';
            }

            $rows[] = [
                'row_number' => $rowNumber,
                'name' => $name,
                'report_label' => ReportSavedViewRegistry::find($reportKey)['label'] ?? $data['report_label'],
                'report_key' => $reportKey,
                'is_default' => in_array($isDefault, ['yes', '1', 'true', 'نعم'], true) ? 'نعم' : 'لا',
                'filter_count' => $filterCount === '' ? 0 : (int) $filterCount,
                'filters_summary' => $data['filters_summary'],
                'status' => $errors === [] ? 'valid' : 'invalid',
                'errors' => $errors,
            ];
        }

        fclose($handle);

        $validRows = count(array_filter($rows, fn (array $row): bool => $row['status'] === 'valid'));

        return [
            'headers' => $headers,
            'header_errors' => [],
            'rows' => $rows,
            'total_rows' => count($rows),
            'valid_rows' => $validRows,
            'invalid_rows' => count($rows) - $validRows,
        ];
    }

    /**
     * @param array<int, mixed> $row
     */
    private function isEmptyCsvRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array{created: int, skipped: int}
     */
    private function applySavedViewImportRows(Request $request, array $rows): array
    {
        return DB::transaction(function () use ($request, $rows): array {
            $created = 0;
            $skipped = 0;

            foreach ($rows as $row) {
                if (($row['status'] ?? '') !== 'valid') {
                    continue;
                }

                $exists = ReportSavedView::query()
                    ->where('user_id', $request->user()->id)
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
                        ->where('user_id', $request->user()->id)
                        ->where('report_key', $row['report_key'])
                        ->update(['is_default' => false]);
                }

                ReportSavedView::query()->create([
                    'user_id' => $request->user()->id,
                    'report_key' => $row['report_key'],
                    'name' => $row['name'],
                    'filters' => [],
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

    /**
     * @return array<int, object{key: string, label: string}>
     */
    private function reportFilterOptions(): array
    {
        return collect(ReportSavedViewRegistry::reports())
            ->map(fn (array $report): object => (object) [
                'key' => $report['key'],
                'label' => $report['label'],
            ])
            ->sortBy('label')
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function matchingReportKeysForSearch(string $search): array
    {
        $needle = mb_strtolower(trim($search), 'UTF-8');

        if ($needle === '') {
            return [];
        }

        return collect(ReportSavedViewRegistry::reports())
            ->filter(function (array $report) use ($needle): bool {
                return str_contains(mb_strtolower($report['key'], 'UTF-8'), $needle)
                    || str_contains(mb_strtolower($report['label'], 'UTF-8'), $needle);
            })
            ->pluck('key')
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function matchingFilterValuesForSearch(string $search): array
    {
        $needle = mb_strtolower(trim($search), 'UTF-8');

        if ($needle === '') {
            return [];
        }

        $maps = [
            self::PAYMENT_STATUS_LABELS,
            self::AGING_BUCKET_LABELS,
        ];

        $matches = [];

        foreach ($maps as $map) {
            foreach ($map as $value => $label) {
                if (str_contains(mb_strtolower($label, 'UTF-8'), $needle)) {
                    $matches[] = (string) $value;
                }
            }
        }

        return array_values(array_unique($matches));
    }

    private function formatSavedView(ReportSavedView $savedView): object
    {
        $filters = $savedView->filters ?? [];

        return (object) [
            'id' => $savedView->id,
            'name' => $savedView->name,
            'report_key' => $savedView->report_key,
            'report_label' => $this->reportLabel($savedView->report_key),
            'is_default' => $savedView->is_default,
            'report_url' => $this->reportUrl($savedView->report_key, $filters),
            'filters' => collect($filters)
                ->map(fn ($value, $key) => [
                    'key' => $key,
                    'label' => self::FILTER_LABELS[$key] ?? $key,
                    'value' => $value,
                    'display_value' => $this->displayFilterValue((string) $key, $value),
                ])
                ->values(),
            'updated_at' => $savedView->updated_at,
        ];
    }


    private function formatReportName(string $reportKey): string
    {
        return $this->reportLabel($reportKey);
    }

    private function reportLabel(string $reportKey): string
    {
        $report = ReportSavedViewRegistry::find($reportKey);

        if (! $report) {
            return $reportKey;
        }

        return $report['label'] ?? $reportKey;
    }

    private function reportRouteName(string $reportKey): ?string
    {
        return ReportSavedViewRegistry::indexRoute($reportKey);
    }


    private function formatFilterKey(string $key): string
    {
        return [
            'customer_id' => 'العميل',
            'supplier_id' => 'المورد',
            'branch_id' => 'الفرع',
            'as_of_date' => 'حتى تاريخ',
            'aging_bucket' => 'شريحة العمر',
            'payment_status' => 'حالة الدفع',
        ][$key] ?? $key;
    }

    private function formatFilterDisplayValue(string $key, mixed $value): string
    {
        return match ($key) {
            'customer_id' => $this->lookupTableDisplayValue('customers', $value, ['name', 'customer_name', 'company_name'])
                ?? $this->formatFilterValue($value),
            'supplier_id' => $this->lookupTableDisplayValue('suppliers', $value, ['name', 'supplier_name', 'company_name'])
                ?? $this->formatFilterValue($value),
            'branch_id' => $this->lookupTableDisplayValue('branches', $value, ['name', 'branch_name'])
                ?? $this->formatFilterValue($value),
            'aging_bucket' => $this->formatAgingBucket((string) $value),
            'payment_status' => $this->formatPaymentStatus((string) $value),
            default => $this->formatFilterValue($value),
        };
    }

    private function formatAgingBucket(string $bucket): string
    {
        return [
            'not_due' => 'غير مستحق',
            'overdue_1_30' => 'متأخر من 1 إلى 30 يوم',
            'overdue_31_60' => 'متأخر من 31 إلى 60 يوم',
            'overdue_61_90' => 'متأخر من 61 إلى 90 يوم',
            'overdue_more_than_90' => 'متأخر أكثر من 90 يوم',
            'without_due_date' => 'بدون تاريخ استحقاق',
        ][$bucket] ?? $bucket;
    }

    private function formatPaymentStatus(string $status): string
    {
        return [
            'all' => 'كل الحالات',
            'paid' => 'مدفوعة',
            'unpaid' => 'غير مدفوعة',
            'partial' => 'مدفوعة جزئيًا',
            'partially_paid' => 'مدفوعة جزئيًا',
            'pending' => 'قيد المتابعة',
            'overdue' => 'متأخرة',
        ][$status] ?? $status;
    }

    private function lookupTableDisplayValue(string $table, mixed $id, array $columns): ?string
    {
        if (! is_numeric($id)) {
            return null;
        }

        try {
            foreach ($columns as $column) {
                if (! DB::getSchemaBuilder()->hasColumn($table, $column)) {
                    continue;
                }

                $value = DB::table($table)
                    ->where('id', $id)
                    ->value($column);

                if ($value !== null && $value !== '') {
                    return (string) $value;
                }
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function formatFilterValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'نعم' : 'لا';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if ($value === null) {
            return '';
        }

        return (string) $value;
    }

    private function reportUrl(string $reportKey, array $filters): ?string
    {
        $routeName = $this->reportRouteName($reportKey);

        if (! $routeName || ! Route::has($routeName)) {
            return null;
        }

        $query = array_filter($filters, fn ($value) => $value !== null && $value !== '');

        return route($routeName, $query);
    }
    private function displayFilterValue(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return match ($key) {
            'customer_id' => $this->entityLabel('customers', (int) $value, 'عميل'),
            'supplier_id' => $this->entityLabel('suppliers', (int) $value, 'مورد'),
            'branch_id' => $this->entityLabel('branches', (int) $value, 'فرع'),
            'payment_status' => self::PAYMENT_STATUS_LABELS[(string) $value] ?? (string) $value,
            'aging_bucket' => self::AGING_BUCKET_LABELS[(string) $value] ?? (string) $value,
            default => (string) $value,
        };
    }

    private function entityLabel(string $table, int $id, string $fallbackLabel): string
    {
        if ($id <= 0) {
            return '-';
        }

        $name = DB::table($table)->where('id', $id)->value('name');

        return $name ? $name . ' #' . $id : $fallbackLabel . ' غير معروف #' . $id;
    }

    private function authorizeSavedView(Request $request, ReportSavedView $savedView): void
    {
        abort_unless((int) $savedView->user_id === (int) $request->user()->id, 404);
    }
}
