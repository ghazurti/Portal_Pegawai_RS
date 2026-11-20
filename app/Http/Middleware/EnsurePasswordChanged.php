<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user || ! $user->wajib_ganti_password) {
            return $next($request);
        }

        $routeName = (string) ($request->route()?->getName() ?? '');
        if (
            $request->routeIs('filament.portal.pages.ganti-password', 'filament.portal.auth.logout')
            || str_starts_with($routeName, 'livewire.')
            || $request->is('livewire/*')
        ) {
            return $next($request);
        }

        return redirect()->route('filament.portal.pages.ganti-password');
    }
}
