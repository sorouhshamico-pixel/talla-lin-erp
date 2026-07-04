<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use Illuminate\Http\Request;

class QuotationItemController extends Controller
{
    public function store(Request $request, Quotation $quotation)
    {
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

        return redirect()->route('quotations.show', $quotation);
    }
}
