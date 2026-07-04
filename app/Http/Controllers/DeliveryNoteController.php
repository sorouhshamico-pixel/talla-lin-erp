<?php

namespace App\Http\Controllers;

use App\Models\DeliveryNote;
use Illuminate\Http\Request;

class DeliveryNoteController extends Controller
{
    public function index()
    {
        $deliveryNotes = DeliveryNote::query()
            ->with(['customer', 'salesOrder'])
            ->latest()
            ->paginate(15);

        return view('delivery-notes.index', compact('deliveryNotes'));
    }


    public function updateStatus(Request $request, DeliveryNote $deliveryNote)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:draft,delivered,cancelled'],
        ]);

        $deliveryNote->update([
            'status' => $validated['status'],
        ]);

        return redirect()->route('delivery-notes.show', $deliveryNote);
    }

    public function show(DeliveryNote $deliveryNote)
    {
        $deliveryNote->load(['customer', 'salesOrder', 'items']);

        return view('delivery-notes.show', compact('deliveryNote'));
    }
}
