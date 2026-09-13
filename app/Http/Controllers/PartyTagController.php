<?php

namespace App\Http\Controllers;

use App\Models\PartyTag;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PartyTagController extends Controller
{
    public function index()
    {
        $partyTags = PartyTag::query()
            ->withCount(['customers', 'suppliers'])
            ->orderBy('applies_to')
            ->orderBy('name')
            ->get();

        return view('party-tags.index', [
            'partyTags' => $partyTags,
        ]);
    }

    public function show(PartyTag $partyTag)
    {
        return view('party-tags.show', [
            'partyTag' => $partyTag,
            'customers' => $partyTag->customers()
                ->latest()
                ->paginate(15, ['*'], 'customers_page'),
            'suppliers' => $partyTag->suppliers()
                ->latest()
                ->paginate(15, ['*'], 'suppliers_page'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'applies_to' => ['required', 'in:customer,supplier,both'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        try {
            DB::transaction(function () use ($validated): void {
                $validated['slug'] = $this->uniqueSlug($validated['name'], $validated['applies_to']);

                PartyTag::query()->create($validated);
            });
        } catch (UniqueConstraintViolationException) {
            return back()
                ->withErrors(['name' => 'حدث تعارض أثناء إنشاء التصنيف، الرجاء إعادة المحاولة.'])
                ->withInput();
        }

        return redirect()
            ->route('party-tags.index')
            ->with('success', 'تم إنشاء التصنيف بنجاح.');
    }

    public function toggleActive(PartyTag $partyTag)
    {
        $partyTag->forceFill([
            'is_active' => ! $partyTag->is_active,
        ])->save();

        return redirect()
            ->route('party-tags.index')
            ->with('success', 'تم تحديث حالة التصنيف بنجاح.');
    }

    public function destroy(PartyTag $partyTag)
    {
        if ($partyTag->customers()->exists() || $partyTag->suppliers()->exists()) {
            return redirect()
                ->route('party-tags.index')
                ->with('error', 'لا يمكن حذف تصنيف مرتبط بعملاء أو موردين.');
        }

        $partyTag->delete();

        return redirect()
            ->route('party-tags.index')
            ->with('success', 'تم حذف التصنيف بنجاح.');
    }

    private function uniqueSlug(string $name, string $appliesTo): string
    {
        $baseSlug = Str::slug($name);

        if ($baseSlug === '') {
            $baseSlug = 'tag-' . substr(md5($name), 0, 10);
        }

        $slug = $baseSlug;
        $counter = 2;

        while (
            PartyTag::query()
                ->where('slug', $slug)
                ->where('applies_to', $appliesTo)
                ->lockForUpdate()
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
