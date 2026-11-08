<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$permissions
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if ($user->status !== 'active') {
            auth()->logout();
            return redirect()->route('login')->withErrors(['email' => 'Your account is inactive. Please contact the administrator.']);
        }

        // Owner has full access to all modules
        if ($user->isOwner()) {
            return $next($request);
        }

        // If no permissions specified, allow access for authenticated user
        if (empty($permissions)) {
            return $next($request);
        }

        // Support pipe-delimited multiple permissions like 'perm.a|perm.b'
        $requiredPerms = [];
        foreach ($permissions as $permGroup) {
            foreach (explode('|', $permGroup) as $perm) {
                $requiredPerms[] = trim($perm);
            }
        }

        foreach ($requiredPerms as $permission) {
            if ($user->can($permission)) {
                return $next($request);
            }
        }

        abort(403, 'You do not have permission to access this page.');
    }
}
