<?php

namespace App\Http\Controllers;

use App\Services\PartyDuplicateService;

class PartyDuplicateController extends Controller
{
    public function index(PartyDuplicateService $service)
    {
        $groups = $service->allGroups();

        return view('party-duplicates.index', [
            'groups' => $groups,
            'totalGroups' => collect($groups)->flatten(1)->count(),
            'totalRecords' => collect($groups)
                ->flatten(1)
                ->sum(function (array $group) {
                    return $group['count'];
                }),
        ]);
    }
}
