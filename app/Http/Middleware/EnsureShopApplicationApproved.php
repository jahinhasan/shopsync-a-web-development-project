<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureShopApplicationApproved
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless(
            $user && $user->role === 'business_owner',
            403
        );

        
        $routeName = $request->route()?->getName();

        // Keep application form and status accessible before approval.
        $applicationRoutes = [
            'owner.shop-application.create',
            'owner.shop-application.store',
            'owner.shop-application.status',
        ];

        if (in_array($routeName, $applicationRoutes, true)) {
            if (
                $routeName === 'owner.shop-application.status'
                && !$user->shopApplication()->exists()
            ) {
                return redirect()->route(
                    'owner.shop-application.create'
                );
            }

            return $next($request);
        }

        $application = $user->shopApplication()->first();

        // No application submitted yet.
        if (!$application) {
            return redirect()
                ->route('owner.shop-application.create')
                ->with('warning', 'Submit your shop application first.');
        }

        // Pending or rejected applications cannot access owner features.
        if ($application->status !== 'approved') {
            return redirect()
                ->route('owner.shop-application.status')
                ->with(
                    'warning',
                    'Your shop must be approved before you can access owner features.'
                );
        }
        return $next($request);
    }
}
