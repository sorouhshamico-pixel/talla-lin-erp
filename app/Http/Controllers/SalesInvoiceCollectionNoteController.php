<?php

namespace App\Http\Controllers;

use App\Models\SalesInvoice;
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
}
