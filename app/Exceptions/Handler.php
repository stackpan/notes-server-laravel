<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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

        $this->renderable(function (ValidationException $e) {
            $response = response()
                ->json([
                    'status' => 'fail',
                    'message' => $e->getMessage()
                ], 400)
                ->withHeaders([
                    'Content-Type' => 'application/json; charset=utf-8'
                ]);

            throw new HttpResponseException($response);
        });

        $this->renderable(function (Throwable $e) {
            if ($e instanceof HttpResponseException) {
                return $e->getResponse();
            }

            if (!config('app.debug')) {
                return response()->json([
                    'status' => 'error',
                    'message'=> 'Maaf, terjadi kegagalan pada server kami.'
                ], 500);
            }
        });
    }

}
