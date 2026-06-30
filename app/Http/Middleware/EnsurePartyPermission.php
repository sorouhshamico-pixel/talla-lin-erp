<?php

namespace App\Http\Middleware;

use App\Services\PartyPermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePartyPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (! app(PartyPermissionService::class)->can($request->user(), $permission)) {
            abort(403, 'لا تملك صلاحية الوصول إلى هذا الإجراء.');
        }

        return $next($request);
    }
}
