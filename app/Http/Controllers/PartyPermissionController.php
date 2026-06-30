<?php

namespace App\Http\Controllers;

use App\Services\PartyPermissionService;

class PartyPermissionController extends Controller
{
    public function index(PartyPermissionService $service)
    {
        return view('party-permissions.index', [
            'permissions' => $service->permissions(),
            'rolePermissions' => $service->rolePermissions(),
        ]);
    }
}
