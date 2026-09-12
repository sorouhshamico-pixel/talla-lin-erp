<?php

namespace App\Http\Controllers;

use App\Models\DeliveryNote;
use App\Models\SalesOrder;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class DeliveryNoteConversionController extends Controller
{
    public function store(SalesOrder $salesOrder): RedirectResponse
    {
        if ($salesOrder->status !== 'confirmed') {
            return redirect()
                ->route('sales-orders.show', $salesOrder)
                ->withErrors(['sales_order_status' => 'لا يمكن إنشاء سند تسليم إلا من أمر بيع مؤكد.']);
        }

        try {
            $deliveryNote = DB::transaction(function () use ($salesOrder) {
                $locked = SalesOrder::query()->whereKey($salesOrder->id)->lockForUpdate()->firstOrFail();

                if ($locked->status !== 'confirmed') {
                    throw new DomainException('sales_order_not_confirmed');
                }

                if (DeliveryNote::query()->where('sales_order_id', $locked->id)->exists()) {
                    throw new DomainException('sales_order_already_converted');
                }

                $locked->load('items');

                $deliveryNote = DeliveryNote::create([
                    'delivery_note_number' => $this->generateDeliveryNoteNumber(),
                    'sales_order_id' => $locked->id,
                    'customer_id' => $locked->customer_id,
                    'delivery_note_date' => now()->toDateString(),
                    'status' => 'draft',
                    'total_amount' => $locked->total_amount,
                    'notes' => $locked->notes,
                ]);

                foreach ($locked->items as $item) {
                    $deliveryNote->items()->create([
                        'description' => $item->description,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'line_total' => $item->line_total,
                    ]);
                }

                return $deliveryNote;
            });
        } catch (DomainException) {
            return redirect()
                ->route('sales-orders.show', $salesOrder)
                ->withErrors(['sales_order_status' => 'تم تحويل أمر البيع هذا مسبقاً إلى سند تسليم.']);
        } catch (UniqueConstraintViolationException) {
            return redirect()
                ->route('sales-orders.show', $salesOrder)
                ->withErrors(['sales_order_status' => 'حدث تعارض أثناء إنشاء رقم سند التسليم، الرجاء إعادة المحاولة.']);
        }

        return redirect('/delivery-notes/' . $deliveryNote->id);
    }

    private function generateDeliveryNoteNumber(): string
    {
        $lastNumber = DeliveryNote::query()
            ->whereNotNull('delivery_note_number')
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('delivery_note_number');

        $nextNumber = 1;

        if ($lastNumber && preg_match('/DN-(\d+)/', $lastNumber, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        return 'DN-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
