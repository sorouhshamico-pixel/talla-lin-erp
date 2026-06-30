<?php

namespace App\Http\Controllers;

use App\Models\PartyContactLog;
use Illuminate\Http\Request;

class PartyFollowUpController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'due');
        $search = trim((string) $request->query('q', ''));

        if (! in_array($status, ['due', 'upcoming', 'completed', 'all'], true)) {
            $status = 'due';
        }

        $query = PartyContactLog::query()
            ->with(['customer', 'supplier'])
            ->whereNotNull('follow_up_at');

        if ($status === 'due') {
            $query
                ->whereNull('follow_up_completed_at')
                ->whereDate('follow_up_at', '<=', now()->toDateString());
        }

        if ($status === 'upcoming') {
            $query
                ->whereNull('follow_up_completed_at')
                ->whereDate('follow_up_at', '>', now()->toDateString());
        }

        if ($status === 'completed') {
            $query->whereNotNull('follow_up_completed_at');
        }

        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner
                    ->where('summary', 'like', '%' . $search . '%')
                    ->orWhere('follow_up_result', 'like', '%' . $search . '%')
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                        $supplierQuery->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        $followUps = $query
            ->orderBy('follow_up_at')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $dueCount = PartyContactLog::query()
            ->whereNotNull('follow_up_at')
            ->whereNull('follow_up_completed_at')
            ->whereDate('follow_up_at', '<=', now()->toDateString())
            ->count();

        $upcomingCount = PartyContactLog::query()
            ->whereNotNull('follow_up_at')
            ->whereNull('follow_up_completed_at')
            ->whereDate('follow_up_at', '>', now()->toDateString())
            ->count();

        $completedCount = PartyContactLog::query()
            ->whereNotNull('follow_up_at')
            ->whereNotNull('follow_up_completed_at')
            ->count();

        $allCount = PartyContactLog::query()
            ->whereNotNull('follow_up_at')
            ->count();

        return view('party-follow-ups.index', [
            'followUps' => $followUps,
            'status' => $status,
            'search' => $search,
            'dueCount' => $dueCount,
            'upcomingCount' => $upcomingCount,
            'completedCount' => $completedCount,
            'allCount' => $allCount,
        ]);
    }

    public function complete(Request $request, PartyContactLog $contactLog)
    {
        $validated = $request->validate([
            'follow_up_result' => ['nullable', 'string', 'max:2000'],
        ]);

        $contactLog->forceFill([
            'follow_up_completed_at' => now(),
            'follow_up_result' => $validated['follow_up_result'] ?? null,
        ])->save();

        return redirect()
            ->route('party-follow-ups.index', ['status' => 'due'])
            ->with('success', 'تم إنهاء المتابعة بنجاح.');
    }

    public function reschedule(Request $request, PartyContactLog $contactLog)
    {
        $validated = $request->validate([
            'follow_up_at' => ['required', 'date'],
            'follow_up_result' => ['nullable', 'string', 'max:2000'],
        ]);

        $contactLog->forceFill([
            'follow_up_at' => $validated['follow_up_at'],
            'follow_up_completed_at' => null,
            'follow_up_result' => $validated['follow_up_result'] ?? $contactLog->follow_up_result,
        ])->save();

        return redirect()
            ->route('party-follow-ups.index', ['status' => 'upcoming'])
            ->with('success', 'تم تأجيل المتابعة بنجاح.');
    }
}
