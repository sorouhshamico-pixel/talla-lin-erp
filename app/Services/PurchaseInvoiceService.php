<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\ProductVariant;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoicePayment;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PurchaseInvoiceService
{
    public function createDraftInvoice(
        Supplier $supplier,
        Branch $branch,
        Warehouse $warehouse,
        User $user,
        array $items,
        ?string $invoiceNumber = null,
        ?string $notes = null
    ): PurchaseInvoice {
        if ($supplier->company_id !== $branch->company_id) {
            throw new InvalidArgumentException('المورد والفرع لا يتبعان نفس الشركة.');
        }

        if ($warehouse->branch_id !== $branch->id) {
            throw new InvalidArgumentException('المستودع لا يتبع الفرع المحدد.');
        }

        if (count($items) === 0) {
            throw new InvalidArgumentException('لا يمكن إنشاء فاتورة شراء بدون منتجات.');
        }

        return DB::transaction(function () use (
            $supplier,
            $branch,
            $warehouse,
            $user,
            $items,
            $invoiceNumber,
            $notes
        ) {
            $invoice = PurchaseInvoice::query()->create([
                'company_id' => $branch->company_id,
                'branch_id' => $branch->id,
                'warehouse_id' => $warehouse->id,
                'supplier_id' => $supplier->id,
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
                'invoice_date' => now(),
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

                $unitCost = array_key_exists('unit_cost', $item) && $item['unit_cost'] !== null
                    ? (float) $item['unit_cost']
                    : (float) ($variant->cost_price ?? $product->cost_price);

                $discountAmount = (float) ($item['discount_amount'] ?? 0);
                $taxRate = (float) ($item['tax_rate'] ?? $product->tax_rate);

                $lineSubtotal = round($quantity * $unitCost, 2);
                $taxableAmount = max($lineSubtotal - $discountAmount, 0);
                $taxAmount = round($taxableAmount * ($taxRate / 100), 2);
                $lineTotal = round($taxableAmount + $taxAmount, 2);

                $invoice->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'description' => $item['description'] ?? $product->name . ' - ' . $variant->displayName(),
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
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

            return $invoice->load(['supplier', 'branch', 'warehouse', 'items.variant']);
        });
    }

    public function receiveInvoice(
        PurchaseInvoice $invoice,
        InventoryStockService $stockService
    ): PurchaseInvoice {
        if ($invoice->status !== 'draft') {
            throw new InvalidArgumentException('لا يمكن اعتماد فاتورة شراء غير مسودة.');
        }

        $invoice->load([
            'warehouse',
            'items.variant.product',
        ]);

        if (! $invoice->warehouse) {
            throw new InvalidArgumentException('فاتورة الشراء غير مرتبطة بمستودع.');
        }

        return DB::transaction(function () use ($invoice, $stockService) {
            foreach ($invoice->items as $item) {
                $stockService->applyMovement(
                    warehouse: $invoice->warehouse,
                    variant: $item->variant,
                    type: 'purchase',
                    direction: 'in',
                    quantity: (float) $item->quantity,
                    unitCost: (float) $item->unit_cost,
                    referenceType: 'purchase_invoice',
                    referenceNumber: $invoice->invoice_number,
                    notes: 'إضافة مخزون بسبب اعتماد فاتورة شراء.'
                );
            }

            $invoice->forceFill([
                'status' => 'received',
                'invoice_date' => now(),
            ])->save();

            return $invoice->refresh()->load(['supplier', 'branch', 'warehouse', 'items.variant']);
        });
    }

    public function recordPayment(
        PurchaseInvoice $invoice,
        User $user,
        float $amount,
        string $method = 'cash',
        ?string $referenceNumber = null,
        ?string $notes = null
    ): PurchaseInvoicePayment {
        if ($invoice->status !== 'received') {
            throw new InvalidArgumentException('لا يمكن تسجيل دفعة على فاتورة شراء غير مستلمة.');
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException('مبلغ الدفعة يجب أن يكون أكبر من صفر.');
        }

        $invoice->refresh();

        $remainingAmount = (float) $invoice->remaining_amount;

        if ($amount > $remainingAmount) {
            throw new InvalidArgumentException('مبلغ الدفعة أكبر من المبلغ المتبقي.');
        }

        return DB::transaction(function () use ($invoice, $user, $amount, $method, $referenceNumber, $notes) {
            $payment = PurchaseInvoicePayment::query()->create([
                'purchase_invoice_id' => $invoice->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'method' => $method,
                'reference_number' => $referenceNumber,
                'notes' => $notes,
                'paid_at' => now(),
            ]);

            $newPaidAmount = round((float) $invoice->paid_amount + $amount, 2);
            $newRemainingAmount = round((float) $invoice->grand_total - $newPaidAmount, 2);

            $paymentStatus = match (true) {
                $newRemainingAmount <= 0.0 => 'paid',
                $newPaidAmount > 0.0 => 'partial',
                default => 'unpaid',
            };

            $invoice->forceFill([
                'paid_amount' => $newPaidAmount,
                'remaining_amount' => max($newRemainingAmount, 0),
                'payment_status' => $paymentStatus,
            ])->save();

            return $payment;
        });
    }

    private function generateInvoiceNumber(int $companyId): string
    {
        $nextNumber = PurchaseInvoice::query()
            ->where('company_id', $companyId)
            ->count() + 1;

        return 'PINV-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
