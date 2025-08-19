<?php

// namespace App\Http\Middleware;

// use Closure;
// use Illuminate\Http\Request;
// use Symfony\Component\HttpFoundation\Response;

// class CheckUserType
// {
//     /**
//      * Handle an incoming request.
//      *
//      * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
//      */
//     public function handle(Request $request, Closure $next): Response
//     {
//         return $next($request);
//     }
// }



namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Passport\Token;
use Laravel\Passport\PersonalAccessTokenResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CheckUserType
{
    public function handle(Request $request, Closure $next, $type)
    {
        $authHeader = $request->header('Authorization');
      
       $user = Auth::user();
    //   dd($user->type);

        if ($user->type != 'Admin') {
            return response()->json([
                    'authenticated' =>false,
                    'valid' => false,
                    'mssg' => [
                        'Unauthenticated.'
                    ]
                ]);
        }

        // Check user type
        // if (($type === 'admin' && !$user->isAdmin()) || ($type === 'employee' && !$user->isEmployee())) {
        //     return response()->json(['message' => 'Unauthorized.'], 403);
        // }

        // Proceed to the next request
        return $next($request);
    }
}

