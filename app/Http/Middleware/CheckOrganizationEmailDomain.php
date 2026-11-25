<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckOrganizationEmailDomain
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $allowed = $user->organizations
            ->contains(fn (Organization $organization): bool => $organization->allowsEmail($user->email));

        if (! $allowed) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('error', 'Die E-Mail-Domain dieses Kontos ist für keinen Ihrer Mandanten freigegeben.');
        }

        return $next($request);
    }
}
