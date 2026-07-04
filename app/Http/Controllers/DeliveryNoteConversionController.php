<?php

namespace App\Http\Controllers;

use App\Models\DeliveryNote;
use App\Models\SalesOrder;
use Illuminate\Http\RedirectResponse;

class DeliveryNoteConversionController extends Controller
{
    public function store(SalesOrder $salesOrder): RedirectResponse
    {
        if ($salesOrder->status !== 'confirmed') {
            return redirect()
                ->route('sales-orders.show', $salesOrder)
                ->withErrors(['sales_order_status' => 'لا يمكن إنشاء سند تسليم إلا من أمر بيع مؤكد.']);
        }

        $salesOrder->load('items');

        $deliveryNote = DeliveryNote::create([
            'delivery_note_number' => $this->generateDeliveryNoteNumber(),
            'sales_order_id' => $salesOrder->id,
            'customer_id' => $salesOrder->customer_id,
            'delivery_note_date' => now()->toDateString(),
            'status' => 'draft',
            'total_amount' => $salesOrder->total_amount,
            'notes' => $salesOrder->notes,
        ]);

        foreach ($salesOrder->items as $item) {
            $deliveryNote->items()->create([
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_total' => $item->line_total,
            ]);
        }

        return redirect('/delivery-notes/' . $deliveryNote->id);
    }

    private function generateDeliveryNoteNumber(): string
    {
        $lastNumber = DeliveryNote::query()
            ->whereNotNull('delivery_note_number')
            ->orderByDesc('id')
            ->value('delivery_note_number');

        $nextNumber = 1;

        if ($lastNumber && preg_match('/DN-(\d+)/', $lastNumber, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        return 'DN-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
