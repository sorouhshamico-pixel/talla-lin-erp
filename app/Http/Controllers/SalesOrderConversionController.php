<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\SalesOrder;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class SalesOrderConversionController extends Controller
{
    public function store(Quotation $quotation): RedirectResponse
    {
        if ($quotation->status !== 'accepted') {
            return redirect()
                ->route('quotations.show', $quotation)
                ->withErrors(['quotation_status' => 'لا يمكن تحويل عرض السعر إلا إذا كانت حالته accepted.']);
        }

        try {
            $salesOrder = DB::transaction(function () use ($quotation) {
                $locked = Quotation::query()->whereKey($quotation->id)->lockForUpdate()->firstOrFail();

                if ($locked->status !== 'accepted') {
                    throw new DomainException('quotation_not_accepted');
                }

                if (SalesOrder::query()->where('quotation_id', $locked->id)->exists()) {
                    throw new DomainException('quotation_already_converted');
                }

                $locked->load('items');

                $salesOrder = SalesOrder::create([
                    'sales_order_number' => $this->generateSalesOrderNumber(),
                    'quotation_id' => $locked->id,
                    'customer_id' => $locked->customer_id,
                    'sales_order_date' => now()->toDateString(),
                    'status' => 'draft',
                    'total_amount' => $locked->total_amount,
                    'notes' => $locked->notes,
                ]);

                foreach ($locked->items as $item) {
                    $salesOrder->items()->create([
                        'description' => $item->description,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'line_total' => $item->line_total,
                    ]);
                }

                return $salesOrder;
            });
        } catch (DomainException) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->withErrors(['quotation_status' => 'تم تحويل عرض السعر هذا مسبقاً إلى أمر بيع.']);
        } catch (UniqueConstraintViolationException) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->withErrors(['quotation_status' => 'حدث تعارض أثناء إنشاء رقم أمر البيع، الرجاء إعادة المحاولة.']);
        }

        return redirect('/sales-orders/' . $salesOrder->id);
    }

    private function generateSalesOrderNumber(): string
    {
        $lastNumber = SalesOrder::query()
            ->whereNotNull('sales_order_number')
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('sales_order_number');

        $nextNumber = 1;

        if ($lastNumber && preg_match('/SO-(\d+)/', $lastNumber, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        return 'SO-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
