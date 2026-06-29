<?php

namespace App\Http\Controllers;


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
        return view('customers.show', compact('customer'));
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

    public function exportCsv()
    {
        $availableColumns = \Illuminate\Support\Facades\Schema::getColumnListing('customers');
        $preferredColumns = array (
  0 => 'id',
  1 => 'name',
  2 => 'phone',
  3 => 'email',
  4 => 'city',
  5 => 'tax_number',
  6 => 'vat_number',
  7 => 'commercial_registration',
  8 => 'address',
  9 => 'notes',
  10 => 'is_active',
  11 => 'created_at',
);
        $headers = array (
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
);

        $columns = array_values(array_filter($preferredColumns, fn ($column) => in_array($column, $availableColumns, true)));

        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            abort(500, 'Unable to create CSV export.');
        }

        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, array_values(array_intersect_key($headers, array_flip($columns))));

        Customer::query()
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

}
