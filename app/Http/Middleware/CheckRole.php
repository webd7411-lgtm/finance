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
            return redirect()->route('login')->withErrors(['email' => 'Aap ka account ghair-faal (inactive) hai. Administrator se rabta karein.']);
        }

        if (!empty($roles) && !in_array($user->role, $roles)) {
            abort(403, 'Aap ke paas is page ko dekhne ke ikhtiyarat (permissions) nahi hain.');
        }

        return $next($request);
    }
}
