<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\DeliveryNote;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SalesInvoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class DeliveryNoteInvoiceController extends Controller
{
    public function store(DeliveryNote $deliveryNote): RedirectResponse
    {
        if ($deliveryNote->status !== 'delivered') {
            return redirect()
                ->route('delivery-notes.show', $deliveryNote)
                ->withErrors(['delivery_note_status' => 'لا يمكن إنشاء فاتورة إلا من سند تسليم بحالة delivered.']);
        }

        if (SalesInvoice::query()->where('delivery_note_id', $deliveryNote->id)->exists()) {
            return redirect()
                ->route('delivery-notes.show', $deliveryNote)
                ->withErrors(['delivery_note_id' => 'تم إنشاء فاتورة لهذا السند مسبقًا.']);
        }

        $deliveryNote->load(['customer', 'items']);

        $branch = Branch::query()
            ->where('company_id', $deliveryNote->customer->company_id)
            ->where('is_active', true)
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->firstOrFail();

        $variant = $this->getDefaultVariant($deliveryNote->customer->company_id);

        if (! $deliveryNote->items()->exists()) {
            return redirect()
                ->route('delivery-notes.show', $deliveryNote)
                ->withErrors([
                    'delivery_note_items' => 'لا يمكن تحويل سند التسليم إلى فاتورة مبيعات بدون بنود.',
                ]);
        }

        $invoice = DB::transaction(function () use ($deliveryNote, $branch, $variant) {
            $invoice = SalesInvoice::query()->create([
                'company_id' => $branch->company_id,
                'branch_id' => $branch->id,
                'customer_id' => $deliveryNote->customer_id,
                'delivery_note_id' => $deliveryNote->id,
                'user_id' => auth()->id(),
                'invoice_number' => $this->generateInvoiceNumber($branch->company_id),
                'status' => 'draft',
                'payment_status' => 'unpaid',
                'currency' => 'SAR',
                'subtotal' => 0,
                'discount_total' => 0,
                'tax_total' => 0,
                'grand_total' => 0,
                'paid_amount' => 0,
                'remaining_amount' => 0,
                'issued_at' => now(),
                'notes' => $deliveryNote->notes,
            ]);

            $subtotal = 0.0;

            foreach ($deliveryNote->items as $index => $item) {
                $lineSubtotal = round((float) $item->quantity * (float) $item->unit_price, 2);

                $invoice->items()->create([
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'description' => $item->description,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'discount_amount' => 0,
                    'tax_rate' => 0,
                    'tax_amount' => 0,
                    'line_subtotal' => $lineSubtotal,
                    'line_total' => $lineSubtotal,
                    'item_order' => $index + 1,
                ]);

                $subtotal += $lineSubtotal;
            }

            $invoice->forceFill([
                'subtotal' => round($subtotal, 2),
                'discount_total' => 0,
                'tax_total' => 0,
                'grand_total' => round($subtotal, 2),
                'remaining_amount' => round($subtotal, 2),
            ])->save();

            return $invoice;
        });

        return redirect()
            ->route('sales-invoices.show', $invoice)
            ->with('success', 'تم تحويل سند التسليم إلى فاتورة مبيعات بنجاح.');
    }

    private function getDefaultVariant(int $companyId): ProductVariant
    {
        $product = Product::query()->firstOrCreate(
            [
                'company_id' => $companyId,
                'sku' => 'DELIVERY-NOTE-SERVICE',
            ],
            [
                'name' => 'خدمة سند تسليم',
                'description' => 'منتج افتراضي لتحويل سند التسليم إلى فاتورة.',
                'type' => 'simple',
                'sale_price' => 0,
                'cost_price' => 0,
                'tax_rate' => 0,
                'track_inventory' => false,
                'is_active' => true,
            ]
        );

        return ProductVariant::query()->firstOrCreate(
            [
                'sku' => 'DELIVERY-NOTE-SERVICE-VARIANT',
            ],
            [
                'product_id' => $product->id,
                'sale_price' => 0,
                'cost_price' => 0,
                'is_active' => true,
            ]
        );
    }

    private function generateInvoiceNumber(int $companyId): string
    {
        $nextNumber = SalesInvoice::query()
            ->where('company_id', $companyId)
            ->count() + 1;

        return 'INV-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
