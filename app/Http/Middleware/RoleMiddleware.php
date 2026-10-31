<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware {

   public function handle(Request $request, Closure $next, ...$roles)
{
    $user = $request->user();

    if (!$user) {
        return response()->json([
            'message' => 'Unauthorized'
        ], 401);
    }

    $parsedRoles = [];
    foreach ($roles as $role) {
        if (str_contains($role, ',')) {
            $parsedRoles = array_merge($parsedRoles, explode(',', $role));
        } else {
            $parsedRoles[] = $role;
        }
    }

    $parsedRoles = array_values(array_filter(array_map('trim', $parsedRoles)));

    if (!in_array($user->role, $parsedRoles, true)) {
        return response()->json([
            'message' => 'Forbidden - You do not have permission'
        ], 403);
    }

    return $next($request);
}
}
