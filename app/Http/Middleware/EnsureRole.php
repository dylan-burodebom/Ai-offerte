<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles)) {
            // Redirect klant trying to access staff pages, instead of hard 403
            if ($user?->isKlant()) {
                return redirect()->route('klant.dashboard');
            }

            abort(403, 'Geen toegang.');
        }

        return $next($request);
    }
}
