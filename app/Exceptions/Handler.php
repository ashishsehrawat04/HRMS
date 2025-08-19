<?php

// namespace App\Exceptions;

// use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
// use Throwable;
// use Illuminate\Auth\AuthenticationException;
// use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
// use Symfony\Component\HttpFoundation\Response;

// class Handler extends ExceptionHandler
// {
//     /**
//      * A list of exception types with their corresponding custom log levels.
//      *
//      * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
//      * 
//      * 
//      */
     
     
//      protected function unauthenticated($request, AuthenticationException $exception)
//     {
//         return response()->json([
//             'status' => 'error',
//             'message' => 'Token is invalid or expired.',
//         ], Response::HTTP_UNAUTHORIZED); // 401
//     }
//     protected $levels = [
//         //
//     ];

//     /**
//      * A list of the exception types that are not reported.
//      *
//      * @var array<int, class-string<\Throwable>>
//      */
//     protected $dontReport = [
//         //
//     ];

//     /**
//      * A list of the inputs that are never flashed to the session on validation exceptions.
//      *
//      * @var array<int, string>
//      */
//     protected $dontFlash = [
//         'current_password',
//         'password',
//         'password_confirmation',
//     ];

//     /**
//      * Register the exception handling callbacks for the application.
//      */
//     public function register(): void
//     {
//         $this->reportable(function (Throwable $e) {
//             //
//         });
//     }
// }

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Customize the unauthenticated response.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Token is invalid or expired.',
        ], Response::HTTP_UNAUTHORIZED); // 401
    }
    
    public function render($request, Throwable $e)
    {
        if ($request->is('api/*')) {
            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'authenticated' =>false,
                    'valid' => false,
                    'mssg' => [
                        'Unauthenticated.'
                    ]
                ]);
            }

            if ($e instanceof InvalidTokenException) {
                return response()->json([
                    'authenticated' =>false,
                    'valid' => false,
                    'mssg' => [
                        'The access token is invalid or has expired.'
                    ]
                ]);
            }

            if ($e instanceof UnauthorizedHttpException) {
                return response()->json([
                    'authenticated' =>false,
                    'valid' => false,
                    'mssg' => [
                        'Unauthorized.'
                    ]
                ]);
            }
        }

        return parent::render($request, $e);

    }
    
}
