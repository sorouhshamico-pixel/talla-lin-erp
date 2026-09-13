<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\SalesOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QuotationItemController extends Controller
{
    public function store(Request $request, Quotation $quotation)
    {
        if ($blocked = $this->rejectIfConverted($quotation)) {
            return $blocked;
        }

        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $quantity = (float) $validated['quantity'];
        $unitPrice = (float) $validated['unit_price'];

        $quotation->items()->create([
            'description' => $validated['description'],
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $quantity * $unitPrice,
        ]);

        $this->updateQuotationTotal($quotation);

        return redirect()->route('quotations.show', $quotation);
    }

    public function update(Request $request, Quotation $quotation, QuotationItem $item)
    {
        abort_unless($item->quotation_id === $quotation->id, 404);

        if ($blocked = $this->rejectIfConverted($quotation)) {
            return $blocked;
        }

        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $quantity = (float) $validated['quantity'];
        $unitPrice = (float) $validated['unit_price'];

        $item->update([
            'description' => $validated['description'],
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $quantity * $unitPrice,
        ]);

        $this->updateQuotationTotal($quotation);

        return redirect()->route('quotations.show', $quotation);
    }

    public function destroy(Quotation $quotation, QuotationItem $item)
    {
        abort_unless($item->quotation_id === $quotation->id, 404);

        if ($blocked = $this->rejectIfConverted($quotation)) {
            return $blocked;
        }

        $item->delete();

        $this->updateQuotationTotal($quotation);

        return redirect()->route('quotations.show', $quotation);
    }

    private function rejectIfConverted(Quotation $quotation): ?RedirectResponse
    {
        if (! SalesOrder::query()->where('quotation_id', $quotation->id)->exists()) {
            return null;
        }

        return redirect()
            ->route('quotations.show', $quotation)
            ->withErrors(['items' => 'لا يمكن تعديل بنود عرض سعر تم تحويله بالفعل إلى أمر بيع.']);
    }

    private function updateQuotationTotal(Quotation $quotation): void
    {
        $quotation->update([
            'total_amount' => $quotation->items()->sum('line_total'),
        ]);
    }
}
