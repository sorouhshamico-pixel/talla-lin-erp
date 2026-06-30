<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PartyAttachment;
use App\Models\PartyContactLog;
use App\Models\PartyNote;
use App\Models\Supplier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PartyTimelineController extends Controller
{
    public function customer(Customer $customer)
    {
        return view('party-timeline.show', [
            'party' => $customer,
            'partyType' => 'customer',
            'partyTypeLabel' => 'عميل',
            'title' => 'خط نشاط العميل',
            'partyShowRoute' => route('customers.show', $customer),
            'timeline' => $this->buildTimeline('customer', $customer->id),
        ]);
    }

    public function supplier(Supplier $supplier)
    {
        return view('party-timeline.show', [
            'party' => $supplier,
            'partyType' => 'supplier',
            'partyTypeLabel' => 'مورد',
            'title' => 'خط نشاط المورد',
            'partyShowRoute' => route('suppliers.show', $supplier),
            'timeline' => $this->buildTimeline('supplier', $supplier->id),
        ]);
    }

    private function buildTimeline(string $type, int $id): Collection
    {
        $foreignKey = $type === 'customer' ? 'customer_id' : 'supplier_id';

        $notes = PartyNote::query()
            ->where($foreignKey, $id)
            ->latest()
            ->limit(50)
            ->get()
            ->map(function (PartyNote $note) {
                return [
                    'type' => 'note',
                    'type_label' => 'ملاحظة',
                    'title' => 'ملاحظة داخلية',
                    'description' => $note->note,
                    'occurred_at' => $note->created_at,
                    'meta' => 'تاريخ الإضافة: ' . $this->formatDateTime($note->created_at),
                ];
            });

        $attachments = PartyAttachment::query()
            ->where($foreignKey, $id)
            ->latest()
            ->limit(50)
            ->get()
            ->map(function (PartyAttachment $attachment) {
                return [
                    'type' => 'attachment',
                    'type_label' => 'مرفق',
                    'title' => 'تم رفع مرفق',
                    'description' => $attachment->original_name,
                    'occurred_at' => $attachment->created_at,
                    'meta' => 'الحجم: ' . number_format(($attachment->size ?? 0) / 1024, 2) . ' KB — النوع: ' . ($attachment->mime_type ?: '-'),
                ];
            });

        $contactLogs = PartyContactLog::query()
            ->where($foreignKey, $id)
            ->latest('contacted_at')
            ->latest()
            ->limit(50)
            ->get()
            ->map(function (PartyContactLog $contactLog) {
                $typeLabels = [
                    'call' => 'اتصال',
                    'whatsapp' => 'واتساب',
                    'email' => 'إيميل',
                    'meeting' => 'اجتماع',
                    'other' => 'أخرى',
                ];

                return [
                    'type' => 'contact_log',
                    'type_label' => 'تواصل',
                    'title' => 'سجل تواصل — ' . ($typeLabels[$contactLog->contact_type] ?? $contactLog->contact_type),
                    'description' => $contactLog->summary,
                    'occurred_at' => $contactLog->contacted_at ?: $contactLog->created_at,
                    'meta' => 'تاريخ التواصل: ' . $this->formatDate($contactLog->contacted_at) . ' — المتابعة: ' . $this->formatDate($contactLog->follow_up_at),
                ];
            });

        return $notes
            ->merge($attachments)
            ->merge($contactLogs)
            ->sortByDesc(function (array $item) {
                return $item['occurred_at']
                    ? Carbon::parse($item['occurred_at'])->timestamp
                    : 0;
            })
            ->values();
    }

    private function formatDate($date): string
    {
        return $date ? Carbon::parse($date)->format('Y-m-d') : '-';
    }

    private function formatDateTime($date): string
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i') : '-';
    }
}
