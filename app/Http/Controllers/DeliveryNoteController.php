<?php

namespace App\Http\Controllers;

use App\Models\DeliveryNote;

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

    public function show(DeliveryNote $deliveryNote)
    {
        $deliveryNote->load(['customer', 'salesOrder', 'items']);

        return view('delivery-notes.show', compact('deliveryNote'));
    }
}
