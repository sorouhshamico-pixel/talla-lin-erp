<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SalesInvoiceService
{
    public function createDraftInvoice(
        Customer $customer,
        Branch $branch,
        User $user,
        array $items,
        ?string $invoiceNumber = null,
        ?string $notes = null
    ): SalesInvoice {
        if ($customer->company_id !== $branch->company_id) {
            throw new InvalidArgumentException('العميل والفرع لا يتبعان نفس الشركة.');
        }

        if (count($items) === 0) {
            throw new InvalidArgumentException('لا يمكن إنشاء فاتورة بدون منتجات.');
        }

        return DB::transaction(function () use (
            $customer,
            $branch,
            $user,
            $items,
            $invoiceNumber,
            $notes
        ) {
            $invoice = SalesInvoice::query()->create([
                'company_id' => $branch->company_id,
                'branch_id' => $branch->id,
                'customer_id' => $customer->id,
                'user_id' => $user->id,
                'invoice_number' => $invoiceNumber ?: $this->generateInvoiceNumber($branch->company_id),
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
                'notes' => $notes,
            ]);

            $subtotal = 0.0;
            $discountTotal = 0.0;
            $taxTotal = 0.0;
            $grandTotal = 0.0;

            foreach ($items as $index => $item) {
                $variant = ProductVariant::query()
                    ->with('product')
                    ->findOrFail($item['product_variant_id']);

                $product = $variant->product;

                if (! $product) {
                    throw new InvalidArgumentException('المتغير غير مرتبط بمنتج.');
                }

                $quantity = (float) ($item['quantity'] ?? 0);

                if ($quantity <= 0) {
                    throw new InvalidArgumentException('كمية المنتج يجب أن تكون أكبر من صفر.');
                }

                $unitPrice = array_key_exists('unit_price', $item) && $item['unit_price'] !== null
                    ? (float) $item['unit_price']
                    : (float) ($variant->sale_price ?? $product->sale_price);

                $discountAmount = (float) ($item['discount_amount'] ?? 0);
                $taxRate = (float) ($item['tax_rate'] ?? $product->tax_rate);

                $lineSubtotal = round($quantity * $unitPrice, 2);
                $taxableAmount = max($lineSubtotal - $discountAmount, 0);
                $taxAmount = round($taxableAmount * ($taxRate / 100), 2);
                $lineTotal = round($taxableAmount + $taxAmount, 2);

                $invoice->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'description' => $item['description'] ?? $product->name . ' - ' . $variant->displayName(),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_amount' => $discountAmount,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount,
                    'line_subtotal' => $lineSubtotal,
                    'line_total' => $lineTotal,
                    'item_order' => $index + 1,
                ]);

                $subtotal += $lineSubtotal;
                $discountTotal += $discountAmount;
                $taxTotal += $taxAmount;
                $grandTotal += $lineTotal;
            }

            $invoice->forceFill([
                'subtotal' => round($subtotal, 2),
                'discount_total' => round($discountTotal, 2),
                'tax_total' => round($taxTotal, 2),
                'grand_total' => round($grandTotal, 2),
                'remaining_amount' => round($grandTotal, 2),
            ])->save();

            return $invoice->load(['customer', 'branch', 'items.variant']);
        });
    }

    public function issueInvoice(SalesInvoice $invoice, InventoryStockService $stockService): SalesInvoice
    {
        if ($invoice->status !== 'draft') {
            throw new InvalidArgumentException('لا يمكن اعتماد فاتورة غير مسودة.');
        }

        $invoice->load(['branch', 'items.variant.product']);

        $warehouse = Warehouse::query()
            ->where('branch_id', $invoice->branch_id)
            ->where('is_active', true)
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->first();

        if (! $warehouse) {
            throw new InvalidArgumentException('لا يوجد مستودع نشط مرتبط بفرع الفاتورة.');
        }

        return DB::transaction(function () use ($invoice, $warehouse, $stockService) {
            foreach ($invoice->items as $item) {
                $stockService->applyMovement(
                    warehouse: $warehouse,
                    variant: $item->variant,
                    type: 'sale',
                    direction: 'out',
                    quantity: (float) $item->quantity,
                    unitCost: (float) ($item->variant?->cost_price ?? 0),
                    referenceType: 'sales_invoice',
                    referenceNumber: $invoice->invoice_number,
                    notes: 'خصم مخزون بسبب اعتماد فاتورة بيع.'
                );
            }

            $invoice->forceFill([
                'status' => 'issued',
                'issued_at' => now(),
            ])->save();

            return $invoice->refresh()->load(['customer', 'branch', 'items.variant']);
        });
    }

    private function generateInvoiceNumber(int $companyId): string
    {
        $nextNumber = SalesInvoice::query()
            ->where('company_id', $companyId)
            ->count() + 1;

        return 'INV-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
