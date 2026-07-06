<?php

namespace App\Http\Controllers;

use App\Models\SalesInvoice;
use App\Models\SalesInvoiceCollectionNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SalesInvoiceCollectionNoteController extends Controller
{
    public function store(Request $request, SalesInvoice $salesInvoice): RedirectResponse
    {
        $data = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
            'follow_up_at' => ['nullable', 'date'],
        ]);

        $salesInvoice->collectionNotes()->create([
            'user_id' => $request->user()?->id,
            'note' => $data['note'],
            'follow_up_at' => $data['follow_up_at'] ?? null,
        ]);

        return redirect()
            ->route('sales-invoices.show', $salesInvoice)
            ->with('success', 'تمت إضافة ملاحظة التحصيل بنجاح.');
    }
    public function complete(Request $request, SalesInvoice $salesInvoice, SalesInvoiceCollectionNote $collectionNote): RedirectResponse
    {
        abort_unless((int) $collectionNote->sales_invoice_id === (int) $salesInvoice->id, 404);

        $data = $request->validate([
            'completion_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $collectionNote->update([
            'completed_at' => now(),
            'completed_by_user_id' => $request->user()?->id,
            'completion_note' => $data['completion_note'] ?? null,
        ]);

        return redirect()
            ->route('sales-invoices.show', $salesInvoice)
            ->with('success', 'تم إغلاق متابعة التحصيل بنجاح.');
    }

}
