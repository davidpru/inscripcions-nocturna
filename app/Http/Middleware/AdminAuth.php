<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si el usuario está autenticado y es admin activo
        if (!Auth::guard('administrador')->check()) {
            return redirect()->route('admin.login');
        }

        $administrador = Auth::guard('administrador')->user();
        
        if (!$administrador->isActivo()) {
            Auth::guard('administrador')->logout();
            return redirect()->route('admin.login')
                ->withErrors(['email' => 'No tens permís per accedir a l\'administració.']);
        }

        return $next($request);
    }
}
