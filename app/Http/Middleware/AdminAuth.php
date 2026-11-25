<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    /**
     * Ensure the user is authenticated as admin.
     */
    public function handle(Request $request, Closure $next, ?string $role = null): Response
    {
        if (!$request->session()->get('admin_authenticated')) {
            return redirect()->guest('/admin/login');
        }

        $sessionRole = $request->session()->get('admin_role');

        if (!$sessionRole) {
            $request->session()->forget(['admin_authenticated', 'admin_user_id', 'admin_role', 'admin_user_name']);
            return redirect()->guest('/admin/login');
        }

        if ($role !== null && $role !== $sessionRole) {
            abort(403, 'Akses tidak diizinkan untuk level jabatan ini.');
        }

        return $next($request);
    }
}
