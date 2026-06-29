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

}
