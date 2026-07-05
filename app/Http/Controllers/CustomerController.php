<?php

namespace App\Http\Controllers;

use App\Models\PartyContactLog;

use App\Models\PartyAttachment;

use App\Models\PartyNote;


use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => $request->query('q'),
            'is_active' => $request->query('is_active'),
        ];

        $customersQuery = DB::table('customers')->orderByDesc('id');

        if (! empty($filters['q'])) {
            $search = '%' . $filters['q'] . '%';

            $customersQuery->where(function ($query) use ($search): void {
                $query
                    ->where('name', 'like', $search)
                    ->orWhere('phone', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('city', 'like', $search)
                    ->orWhere('tax_number', 'like', $search);
            });
        }

        if ($filters['is_active'] === '1' || $filters['is_active'] === '0') {
            $customersQuery->where('is_active', $filters['is_active'] === '1');
        }

        return view('customers.index', [
            'customers' => $customersQuery->get(),
            'filters' => $filters,
            'summary' => [
                'total' => DB::table('customers')->count(),
                'active' => DB::table('customers')->where('is_active', true)->count(),
                'inactive' => DB::table('customers')->where('is_active', false)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('customers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedCustomerData($request);

        DB::table('customers')->insert([
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
            ->route('customers.index')
            ->with('status', 'تم إضافة العميل بنجاح.');
    }

    public function edit(int $customer): View
    {
        $customerRecord = DB::table('customers')->where('id', $customer)->first();

        abort_if($customerRecord === null, 404);

        return view('customers.edit', [
            'customer' => $customerRecord,
        ]);
    }

    public function update(Request $request, int $customer): RedirectResponse
    {
        $customerExists = DB::table('customers')->where('id', $customer)->exists();

        abort_if(! $customerExists, 404);

        $validated = $this->validatedCustomerData($request);

        DB::table('customers')
            ->where('id', $customer)
            ->update([
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'city' => $validated['city'] ?? null,
                'address' => $validated['address'] ?? null,
                'tax_number' => $validated['tax_number'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? true),
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('customers.index')
            ->with('status', 'تم تحديث العميل بنجاح.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedCustomerData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:500'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
    public function show(Customer $customer)
    {
        $customerSalesInvoiceQuery = $customer->salesInvoices();

        $customerSalesInvoiceSummary = [
            'count' => (clone $customerSalesInvoiceQuery)->count(),
            'grand_total' => round((float) (clone $customerSalesInvoiceQuery)->sum('grand_total'), 2),
            'paid_amount' => round((float) (clone $customerSalesInvoiceQuery)->sum('paid_amount'), 2),
            'remaining_amount' => round((float) (clone $customerSalesInvoiceQuery)->sum('remaining_amount'), 2),
        ];

        $customerRecentSalesInvoices = $customer->salesInvoices()
            ->latest('issued_at')
            ->latest('id')
            ->limit(5)
            ->get();

        $customerOutstandingSalesInvoiceQuery = $customer->salesInvoices()
            ->where('remaining_amount', '>', 0);

        $customerOutstandingSalesInvoiceSummary = [
            'count' => (clone $customerOutstandingSalesInvoiceQuery)->count(),
            'remaining_amount' => round((float) (clone $customerOutstandingSalesInvoiceQuery)->sum('remaining_amount'), 2),
        ];

        return view('customers.show', compact(
            'customer',
            'customerSalesInvoiceSummary',
            'customerRecentSalesInvoices',
            'customerOutstandingSalesInvoiceSummary'
        ));
    }

    public function toggleActive(Customer $customer)
    {
        $customer->update([
            'is_active' => ! (bool) $customer->is_active,
        ]);

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'تم تحديث حالة العميل بنجاح.');
    }

    public function exportCsv(\Illuminate\Http\Request $request)
    {
        $availableColumns = \Illuminate\Support\Facades\Schema::getColumnListing('customers');

        $preferredColumns = [
            'id',
            'name',
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
            'name' => 'اسم العميل',
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

        $query = Customer::query();

        $search = trim((string) $request->query('q', ''));

        if ($search !== '') {
            $searchableColumns = array_values(array_filter([
                'name',
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
            'Content-Disposition' => 'attachment; filename="customers-' . now()->format('Y-m-d-His') . '.csv"',
        ]);
    }

    public function exportTemplateCsv()
    {
        $headers = [
            'اسم العميل',
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
            'Content-Disposition' => 'attachment; filename="customers-template.csv"',
        ]);
    }

    public function importCsv(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $availableColumns = \Illuminate\Support\Facades\Schema::getColumnListing('customers');

        $filePath = $request->file('csv_file')->getRealPath();
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            abort(500, 'Unable to read CSV file.');
        }

        $headers = fgetcsv($handle);

        if ($headers === false) {
            fclose($handle);

            return redirect()
                ->route('customers.index')
                ->with('success', 'لم يتم استيراد أي عملاء. الملف فارغ.');
        }

        $headers = array_map(function ($header) {
            $header = trim((string) $header);
            $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);

            return $header;
        }, $headers);

        $map = [
            'اسم العميل' => 'name',
            'name' => 'name',
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
                $lookup = Customer::query()
                    ->when(isset($data['company_id']), fn ($query) => $query->where('company_id', $data['company_id']))
                    ->where('phone', $data['phone'])
                    ->first();
            }

            if ($lookup) {
                $lookup->forceFill($data)->save();
                $updated++;
            } else {
                Customer::unguarded(fn () => Customer::query()->create($data));
                $imported++;
            }
        }

        fclose($handle);

        return redirect()
            ->route('customers.index')
            ->with('success', "تم استيراد العملاء. جديد: {$imported}، محدث: {$updated}، متخطى: {$skipped}.");
    }

    public function bulkUpdateStatus(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'is_active' => ['required', 'boolean'],
        ]);

        $query = Customer::query()
            ->whereIn('id', $validated['ids']);

        if (
            \Illuminate\Support\Facades\Schema::hasColumn('customers', 'company_id')
            && $request->user()?->company_id
        ) {
            $query->where('company_id', $request->user()->company_id);
        }

        $updated = $query->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('customers.index')
            ->with('success', "تم تحديث حالة {$updated} عميل.");
    }


    public function storeNote(\Illuminate\Http\Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
        ]);

        PartyNote::unguarded(function () use ($request, $customer, $validated) {
            PartyNote::query()->create([
                'company_id' => $request->user()?->company_id,
                'user_id' => $request->user()?->id,
                'customer_id' => $customer->id,
                'note' => $validated['note'],
            ]);
        });

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'تمت إضافة ملاحظة العميل بنجاح.');
    }

    public function destroyNote(Customer $customer, PartyNote $note)
    {
        abort_unless((int) $note->customer_id === (int) $customer->id, 404);

        $note->delete();

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'تم حذف ملاحظة العميل بنجاح.');
    }

    public function storeAttachment(\Illuminate\Http\Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'attachment' => ['required', 'file', 'max:4096'],
        ]);

        $file = $validated['attachment'];
        $path = $file->store('party-attachments/customers');

        PartyAttachment::unguarded(function () use ($request, $customer, $file, $path) {
            PartyAttachment::query()->create([
                'company_id' => $request->user()?->company_id,
                'user_id' => $request->user()?->id,
                'customer_id' => $customer->id,
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize() ?: 0,
            ]);
        });

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'تم رفع مرفق العميل بنجاح.');
    }

    public function downloadAttachment(Customer $customer, PartyAttachment $attachment)
    {
        abort_unless((int) $attachment->customer_id === (int) $customer->id, 404);
        abort_unless(\Illuminate\Support\Facades\Storage::exists($attachment->path), 404);

        return \Illuminate\Support\Facades\Storage::download($attachment->path, $attachment->original_name);
    }

    public function destroyAttachment(Customer $customer, PartyAttachment $attachment)
    {
        abort_unless((int) $attachment->customer_id === (int) $customer->id, 404);

        if (\Illuminate\Support\Facades\Storage::exists($attachment->path)) {
            \Illuminate\Support\Facades\Storage::delete($attachment->path);
        }

        $attachment->delete();

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'تم حذف مرفق العميل بنجاح.');
    }

    public function storeContactLog(\Illuminate\Http\Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'contact_type' => ['required', 'string', 'in:call,whatsapp,email,meeting,other'],
            'summary' => ['required', 'string', 'max:2000'],
            'contacted_at' => ['nullable', 'date'],
            'follow_up_at' => ['nullable', 'date'],
        ]);

        PartyContactLog::unguarded(function () use ($request, $customer, $validated) {
            PartyContactLog::query()->create([
                'company_id' => $request->user()?->company_id,
                'user_id' => $request->user()?->id,
                'customer_id' => $customer->id,
                'contact_type' => $validated['contact_type'],
                'summary' => $validated['summary'],
                'contacted_at' => $validated['contacted_at'] ?? now()->toDateString(),
                'follow_up_at' => $validated['follow_up_at'] ?? null,
            ]);
        });

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'تمت إضافة سجل تواصل العميل بنجاح.');
    }

    public function destroyContactLog(Customer $customer, PartyContactLog $contactLog)
    {
        abort_unless((int) $contactLog->customer_id === (int) $customer->id, 404);

        $contactLog->delete();

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'تم حذف سجل تواصل العميل بنجاح.');
    }
}
