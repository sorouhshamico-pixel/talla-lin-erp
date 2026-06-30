<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PartyTag;
use App\Models\Supplier;
use Illuminate\Http\Request;

class PartyClassificationController extends Controller
{
    public function customer(Request $request, Customer $customer)
    {
        $tagId = $this->validatedTagId($request, 'customer');

        $customer->forceFill([
            'party_tag_id' => $tagId,
        ])->save();

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'تم تحديث تصنيف العميل بنجاح.');
    }

    public function supplier(Request $request, Supplier $supplier)
    {
        $tagId = $this->validatedTagId($request, 'supplier');

        $supplier->forceFill([
            'party_tag_id' => $tagId,
        ])->save();

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', 'تم تحديث تصنيف المورد بنجاح.');
    }

    private function validatedTagId(Request $request, string $partyType): ?int
    {
        $validated = $request->validate([
            'party_tag_id' => ['nullable', 'integer', 'exists:party_tags,id'],
        ]);

        $tagId = $validated['party_tag_id'] ?? null;

        if (! $tagId) {
            return null;
        }

        $tag = PartyTag::query()
            ->where('id', $tagId)
            ->where('is_active', true)
            ->firstOrFail();

        $isAllowed = $partyType === 'customer'
            ? $tag->appliesToCustomers()
            : $tag->appliesToSuppliers();

        if (! $isAllowed) {
            abort(422, 'هذا التصنيف غير مناسب لنوع السجل المحدد.');
        }

        return (int) $tagId;
    }
}
