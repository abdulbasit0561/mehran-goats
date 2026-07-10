<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSuperAdmin
{
    /**
     * Only allow users with the hidden is_super_admin flag.
     * Everyone else gets a 403 — the route is invisible to them in the UI.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Access denied.');
        }

        return $next($request);
    }
}
