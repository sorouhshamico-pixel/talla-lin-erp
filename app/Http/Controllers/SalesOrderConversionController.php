<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\SalesOrder;
use Illuminate\Http\RedirectResponse;

class SalesOrderConversionController extends Controller
{
    public function store(Quotation $quotation): RedirectResponse
    {
        if ($quotation->status !== 'accepted') {
            return redirect()
                ->route('quotations.show', $quotation)
                ->withErrors(['quotation_status' => 'لا يمكن تحويل عرض السعر إلا إذا كانت حالته accepted.']);
        }

        $quotation->load('items');

        $salesOrder = SalesOrder::create([
            'sales_order_number' => $this->generateSalesOrderNumber(),
            'quotation_id' => $quotation->id,
            'customer_id' => $quotation->customer_id,
            'sales_order_date' => now()->toDateString(),
            'status' => 'draft',
            'total_amount' => $quotation->total_amount,
            'notes' => $quotation->notes,
        ]);

        foreach ($quotation->items as $item) {
            $salesOrder->items()->create([
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_total' => $item->line_total,
            ]);
        }

        return redirect('/sales-orders/' . $salesOrder->id);
    }

    private function generateSalesOrderNumber(): string
    {
        $lastNumber = SalesOrder::query()
            ->whereNotNull('sales_order_number')
            ->orderByDesc('id')
            ->value('sales_order_number');

        $nextNumber = 1;

        if ($lastNumber && preg_match('/SO-(\d+)/', $lastNumber, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        return 'SO-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
