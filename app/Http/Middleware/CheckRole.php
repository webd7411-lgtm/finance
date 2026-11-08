<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if ($user->status !== 'active') {
            auth()->logout();
            return redirect()->route('login')->withErrors(['email' => 'Your account is inactive. Please contact the administrator.']);
        }

        if (!empty($roles)) {
            $hasRole = false;
            if (method_exists($user, 'hasAnyRole')) {
                $hasRole = $user->hasAnyRole($roles);
            }
            if (!$hasRole && !in_array($user->role, $roles)) {
                abort(403, 'Unauthorized access.');
            }
        }

        return $next($request);
    }
}
