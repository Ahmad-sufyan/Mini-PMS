<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Sign out users whose account was deactivated after they logged in
     * (existing sessions, "remember me" cookies, etc.).
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ! $user->is_active) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            Inertia::flash('toast', ['type' => 'error', 'message' => __('Your account has been deactivated.')]);

            return to_route('login');
        }

        return $next($request);
    }
}
