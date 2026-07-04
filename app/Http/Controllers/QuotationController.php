<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Quotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class QuotationController extends Controller
{
    public function index()
    {
        $quotations = Quotation::query()
            ->with('customer')
            ->latest()
            ->paginate(15);

        return view('quotations.index', compact('quotations'));
    }

    public function create()
    {
        $customers = Customer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('quotations.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'quotation_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:quotation_date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:quotation_date'],
            'notes' => ['nullable', 'string'],
        ]);

        $quotationData = [
            'quotation_number' => $this->generateQuotationNumber(),
            'customer_id' => $validated['customer_id'],
            'quotation_date' => $validated['quotation_date'],
            'status' => 'draft',
            'notes' => $validated['notes'] ?? null,
        ];

        $expiryDate = $validated['valid_until'] ?? $validated['expiry_date'] ?? null;

        if (Schema::hasColumn('quotations', 'valid_until')) {
            $quotationData['valid_until'] = $expiryDate;
        }

        if (Schema::hasColumn('quotations', 'expiry_date')) {
            $quotationData['expiry_date'] = $expiryDate;
        }

        if (Schema::hasColumn('quotations', 'created_by')) {
            $quotationData['created_by'] = $request->user()?->id;
        }

        $quotation = Quotation::create($quotationData);

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('success', 'تم إنشاء عرض السعر بنجاح.');
    }


    public function updateStatus(Request $request, Quotation $quotation)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:draft,sent,accepted,rejected,expired'],
        ]);

        $quotation->update([
            'status' => $validated['status'],
        ]);

        return redirect()->route('quotations.show', $quotation);
    }


    public function print(Quotation $quotation)
    {
        $quotation->load(['customer', 'items']);

        return view('quotations.print', compact('quotation'));
    }

    public function show(Quotation $quotation)
    {
        $quotation->load(['customer', 'creator', 'items']);

        return view('quotations.show', compact('quotation'));
    }

    private function generateQuotationNumber(): string
    {
        $lastNumber = Quotation::query()
            ->whereNotNull('quotation_number')
            ->orderByDesc('id')
            ->value('quotation_number');

        $nextNumber = 1;

        if ($lastNumber && preg_match('/QT-(\d+)/', $lastNumber, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        return 'QT-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
