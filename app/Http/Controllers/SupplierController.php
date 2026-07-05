<?php

namespace App\Http\Controllers;

use App\Models\PartyContactLog;

use App\Models\PartyAttachment;

use App\Models\PartyNote;


use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => $request->query('q'),
            'is_active' => $request->query('is_active'),
        ];

        $suppliersQuery = DB::table('suppliers')->orderByDesc('id');

        if (! empty($filters['q'])) {
            $search = '%' . $filters['q'] . '%';

            $suppliersQuery->where(function ($query) use ($search): void {
                $query
                    ->where('name', 'like', $search)
                    ->orWhere('phone', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('city', 'like', $search)
                    ->orWhere('tax_number', 'like', $search);
            });
        }

        if ($filters['is_active'] === '1' || $filters['is_active'] === '0') {
            $suppliersQuery->where('is_active', $filters['is_active'] === '1');
        }

        return view('suppliers.index', [
            'suppliers' => $suppliersQuery->get(),
            'filters' => $filters,
            'summary' => [
                'total' => DB::table('suppliers')->count(),
                'active' => DB::table('suppliers')->where('is_active', true)->count(),
                'inactive' => DB::table('suppliers')->where('is_active', false)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('suppliers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:500'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::table('suppliers')->insert([
            'company_id' => DB::table('companies')->value('id'),
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'city' => $validated['city'] ?? null,
            'address' => $validated['address'] ?? null,
            'tax_number' => $validated['tax_number'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('suppliers.index')
            ->with('status', 'تم إضافة المورد بنجاح.');
    }
    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'vat_number' => ['nullable', 'string', 'max:50'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'commercial_registration' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('suppliers');
        $data = array_intersect_key($validated, array_flip($columns));

        if (in_array('is_active', $columns, true)) {
            $data['is_active'] = $request->boolean('is_active');
        }

        $supplier->update($data);

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'تم تحديث بيانات المورد بنجاح.');
    }

    public function show(Supplier $supplier)
    {
        $supplierExpenseQuery = $supplier->expenses();

        $supplierExpenseSummary = [
            'count' => (clone $supplierExpenseQuery)->count(),
            'amount' => round((float) (clone $supplierExpenseQuery)->sum('amount'), 2),
        ];

        $supplierRecentExpenses = $supplier->expenses()
            ->latest('expense_date')
            ->latest('id')
            ->limit(5)
            ->get();

        $supplierUnpaidExpenseQuery = $supplier->expenses()
            ->where('is_paid', false);

        $supplierUnpaidExpenseSummary = [
            'count' => (clone $supplierUnpaidExpenseQuery)->count(),
            'amount' => round((float) (clone $supplierUnpaidExpenseQuery)->sum('amount'), 2),
        ];

        return view('suppliers.show', compact(
            'supplier',
            'supplierExpenseSummary',
            'supplierRecentExpenses',
            'supplierUnpaidExpenseSummary'
        ));
    }

    public function toggleActive(Supplier $supplier)
    {
        $supplier->update([
            'is_active' => ! (bool) $supplier->is_active,
        ]);

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', 'تم تحديث حالة المورد بنجاح.');
    }

    public function exportCsv(\Illuminate\Http\Request $request)
    {
        $availableColumns = \Illuminate\Support\Facades\Schema::getColumnListing('suppliers');

        $preferredColumns = [
            'id',
            'name',
            'contact_name',
            'contact_person',
            'phone',
            'email',
            'city',
            'tax_number',
            'vat_number',
            'commercial_registration',
            'address',
            'notes',
            'is_active',
            'created_at',
        ];

        $headers = [
            'id' => 'ID',
            'name' => 'اسم المورد',
            'contact_name' => 'مسؤول التواصل',
            'contact_person' => 'مسؤول التواصل',
            'phone' => 'الهاتف',
            'email' => 'البريد الإلكتروني',
            'city' => 'المدينة',
            'tax_number' => 'الرقم الضريبي',
            'vat_number' => 'الرقم الضريبي',
            'commercial_registration' => 'السجل التجاري',
            'address' => 'العنوان',
            'notes' => 'ملاحظات',
            'is_active' => 'الحالة',
            'created_at' => 'تاريخ الإضافة',
        ];

        $columns = array_values(array_filter(
            $preferredColumns,
            fn ($column) => in_array($column, $availableColumns, true)
        ));

        $query = Supplier::query();

        $search = trim((string) $request->query('q', ''));

        if ($search !== '') {
            $searchableColumns = array_values(array_filter([
                'name',
                'contact_name',
                'contact_person',
                'phone',
                'email',
                'city',
                'tax_number',
                'vat_number',
                'commercial_registration',
                'address',
                'notes',
            ], fn ($column) => in_array($column, $availableColumns, true)));

            if ($searchableColumns !== []) {
                $query->where(function ($innerQuery) use ($search, $searchableColumns) {
                    foreach ($searchableColumns as $index => $column) {
                        $method = $index === 0 ? 'where' : 'orWhere';
                        $innerQuery->{$method}($column, 'like', "%{$search}%");
                    }
                });
            }
        }

        $activeFilter = $request->query('is_active');

        if (in_array('is_active', $availableColumns, true) && in_array((string) $activeFilter, ['0', '1'], true)) {
            $query->where('is_active', (bool) (int) $activeFilter);
        }

        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            abort(500, 'Unable to create CSV export.');
        }

        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, array_values(array_intersect_key($headers, array_flip($columns))));

        $query
            ->orderBy('id')
            ->chunk(200, function ($records) use ($handle, $columns) {
                foreach ($records as $record) {
                    $row = [];

                    foreach ($columns as $column) {
                        $value = $record->{$column};

                        if (is_bool($value)) {
                            $value = $value ? '1' : '0';
                        }

                        $row[] = $value;
                    }

                    fputcsv($handle, $row);
                }
            });

        rewind($handle);

        $csv = stream_get_contents($handle);

        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="suppliers-' . now()->format('Y-m-d-His') . '.csv"',
        ]);
    }

    public function exportTemplateCsv()
    {
        $headers = [
            'اسم المورد',
            'مسؤول التواصل',
            'الهاتف',
            'البريد الإلكتروني',
            'المدينة',
            'الرقم الضريبي',
            'السجل التجاري',
            'العنوان',
            'ملاحظات',
            'الحالة',
        ];

        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            abort(500, 'Unable to create CSV template.');
        }

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $headers);

        rewind($handle);

        $csv = stream_get_contents($handle);

        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="suppliers-template.csv"',
        ]);
    }

    public function importCsv(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $availableColumns = \Illuminate\Support\Facades\Schema::getColumnListing('suppliers');

        $filePath = $request->file('csv_file')->getRealPath();
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            abort(500, 'Unable to read CSV file.');
        }

        $headers = fgetcsv($handle);

        if ($headers === false) {
            fclose($handle);

            return redirect()
                ->route('suppliers.index')
                ->with('success', 'لم يتم استيراد أي موردين. الملف فارغ.');
        }

        $headers = array_map(function ($header) {
            $header = trim((string) $header);
            $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);

            return $header;
        }, $headers);

        $contactColumn = in_array('contact_name', $availableColumns, true) ? 'contact_name' : 'contact_person';

        $map = [
            'اسم المورد' => 'name',
            'name' => 'name',
            'مسؤول التواصل' => $contactColumn,
            'contact_name' => 'contact_name',
            'contact_person' => 'contact_person',
            'الهاتف' => 'phone',
            'phone' => 'phone',
            'البريد الإلكتروني' => 'email',
            'email' => 'email',
            'المدينة' => 'city',
            'city' => 'city',
            'الرقم الضريبي' => in_array('tax_number', $availableColumns, true) ? 'tax_number' : 'vat_number',
            'tax_number' => 'tax_number',
            'vat_number' => 'vat_number',
            'السجل التجاري' => 'commercial_registration',
            'commercial_registration' => 'commercial_registration',
            'العنوان' => 'address',
            'address' => 'address',
            'ملاحظات' => 'notes',
            'notes' => 'notes',
            'الحالة' => 'is_active',
            'is_active' => 'is_active',
        ];

        $imported = 0;
        $updated = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $data = [];

            foreach ($headers as $index => $header) {
                if (! isset($map[$header])) {
                    continue;
                }

                $column = $map[$header];

                if (! in_array($column, $availableColumns, true)) {
                    continue;
                }

                $data[$column] = isset($row[$index]) ? trim((string) $row[$index]) : null;
            }

            if (empty($data['name'])) {
                $skipped++;
                continue;
            }

            if (in_array('is_active', $availableColumns, true)) {
                $value = $data['is_active'] ?? '1';
                $data['is_active'] = in_array(mb_strtolower((string) $value), ['1', 'true', 'yes', 'active', 'نشط', 'فعال'], true);
            }

            if (in_array('company_id', $availableColumns, true)) {
                $companyId = $request->user()?->company_id
                    ?? \Illuminate\Support\Facades\DB::table('companies')->value('id');

                if ($companyId) {
                    $data['company_id'] = $companyId;
                }
            }

            if (in_array('branch_id', $availableColumns, true)) {
                $branchId = $request->user()?->branch_id;

                if (! $branchId && \Illuminate\Support\Facades\Schema::hasTable('branches')) {
                    $branchQuery = \Illuminate\Support\Facades\DB::table('branches');

                    if (isset($data['company_id']) && in_array('company_id', \Illuminate\Support\Facades\Schema::getColumnListing('branches'), true)) {
                        $branchQuery->where('company_id', $data['company_id']);
                    }

                    $branchId = $branchQuery->value('id');
                }

                if ($branchId) {
                    $data['branch_id'] = $branchId;
                }
            }

            $lookup = null;

            if (! empty($data['phone'])) {
                $lookup = Supplier::query()
                    ->when(isset($data['company_id']), fn ($query) => $query->where('company_id', $data['company_id']))
                    ->where('phone', $data['phone'])
                    ->first();
            }

            if ($lookup) {
                $lookup->forceFill($data)->save();
                $updated++;
            } else {
                Supplier::unguarded(fn () => Supplier::query()->create($data));
                $imported++;
            }
        }

        fclose($handle);

        return redirect()
            ->route('suppliers.index')
            ->with('success', "تم استيراد الموردين. جديد: {$imported}، محدث: {$updated}، متخطى: {$skipped}.");
    }

    public function bulkUpdateStatus(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'is_active' => ['required', 'boolean'],
        ]);

        $query = Supplier::query()
            ->whereIn('id', $validated['ids']);

        if (
            \Illuminate\Support\Facades\Schema::hasColumn('suppliers', 'company_id')
            && $request->user()?->company_id
        ) {
            $query->where('company_id', $request->user()->company_id);
        }

        $updated = $query->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('suppliers.index')
            ->with('success', "تم تحديث حالة {$updated} مورد.");
    }


    public function storeNote(\Illuminate\Http\Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
        ]);

        PartyNote::unguarded(function () use ($request, $supplier, $validated) {
            PartyNote::query()->create([
                'company_id' => $request->user()?->company_id,
                'user_id' => $request->user()?->id,
                'supplier_id' => $supplier->id,
                'note' => $validated['note'],
            ]);
        });

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', 'تمت إضافة ملاحظة المورد بنجاح.');
    }

    public function destroyNote(Supplier $supplier, PartyNote $note)
    {
        abort_unless((int) $note->supplier_id === (int) $supplier->id, 404);

        $note->delete();

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', 'تم حذف ملاحظة المورد بنجاح.');
    }

    public function storeAttachment(\Illuminate\Http\Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'attachment' => ['required', 'file', 'max:4096'],
        ]);

        $file = $validated['attachment'];
        $path = $file->store('party-attachments/suppliers');

        PartyAttachment::unguarded(function () use ($request, $supplier, $file, $path) {
            PartyAttachment::query()->create([
                'company_id' => $request->user()?->company_id,
                'user_id' => $request->user()?->id,
                'supplier_id' => $supplier->id,
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize() ?: 0,
            ]);
        });

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', 'تم رفع مرفق المورد بنجاح.');
    }

    public function downloadAttachment(Supplier $supplier, PartyAttachment $attachment)
    {
        abort_unless((int) $attachment->supplier_id === (int) $supplier->id, 404);
        abort_unless(\Illuminate\Support\Facades\Storage::exists($attachment->path), 404);

        return \Illuminate\Support\Facades\Storage::download($attachment->path, $attachment->original_name);
    }

    public function destroyAttachment(Supplier $supplier, PartyAttachment $attachment)
    {
        abort_unless((int) $attachment->supplier_id === (int) $supplier->id, 404);

        if (\Illuminate\Support\Facades\Storage::exists($attachment->path)) {
            \Illuminate\Support\Facades\Storage::delete($attachment->path);
        }

        $attachment->delete();

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', 'تم حذف مرفق المورد بنجاح.');
    }

    public function storeContactLog(\Illuminate\Http\Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'contact_type' => ['required', 'string', 'in:call,whatsapp,email,meeting,other'],
            'summary' => ['required', 'string', 'max:2000'],
            'contacted_at' => ['nullable', 'date'],
            'follow_up_at' => ['nullable', 'date'],
        ]);

        PartyContactLog::unguarded(function () use ($request, $supplier, $validated) {
            PartyContactLog::query()->create([
                'company_id' => $request->user()?->company_id,
                'user_id' => $request->user()?->id,
                'supplier_id' => $supplier->id,
                'contact_type' => $validated['contact_type'],
                'summary' => $validated['summary'],
                'contacted_at' => $validated['contacted_at'] ?? now()->toDateString(),
                'follow_up_at' => $validated['follow_up_at'] ?? null,
            ]);
        });

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', 'تمت إضافة سجل تواصل المورد بنجاح.');
    }

    public function destroyContactLog(Supplier $supplier, PartyContactLog $contactLog)
    {
        abort_unless((int) $contactLog->supplier_id === (int) $supplier->id, 404);

        $contactLog->delete();

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', 'تم حذف سجل تواصل المورد بنجاح.');
    }
}
