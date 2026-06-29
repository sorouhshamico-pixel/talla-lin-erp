<?php

namespace App\Http\Controllers;


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
        return view('suppliers.show', compact('supplier'));
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

}
