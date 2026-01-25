<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminBasicAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $username = config('admin.username');
        $password = config('admin.password');

        if ($request->getUser() !== $username || $request->getPassword() !== $password) {
            return response('Accés denegat.', 401, [
                'WWW-Authenticate' => 'Basic realm="Administració Nocturna"'
            ]);
        }

        return $next($request);
    }
}
