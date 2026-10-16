<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    private $errorReference;

    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        $this->errorReference = (string) \Illuminate\Support\Str::uuid();
        try {
            if ($exception instanceof \Illuminate\Database\QueryException) {
                // QueryException messages can contain bound passwords, tokens or personal data.
                $frames = array_map(function ($frame) {
                    return array_intersect_key($frame, array_flip([
                        'file',
                        'line',
                        'class',
                        'function',
                    ]));
                }, array_slice($exception->getTrace(), 0, 15));
                \Illuminate\Support\Facades\Log::error('Database operation failed', [
                    'error_reference' => $this->errorReference,
                    'exception' => get_class($exception),
                    'sqlstate' => $exception->errorInfo[0] ?? $exception->getCode(),
                    'frames' => $frames,
                ]);
                return;
            }
            parent::report($exception);
        } catch (Throwable $loggingFailure) {
            \App\Support\SecondaryFailure::report('Exception reporting failed', $exception, $loggingFailure);
        }
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    protected function context()
    {
        return array_merge(parent::context(), ['error_reference' => $this->errorReference]);
    }

    public function render($request, Throwable $exception)
    {
        $response = parent::render($request, $exception);
        if ($response->getStatusCode() >= 500 && !config('app.debug')) {
            $reference = $this->errorReference ?: (string) \Illuminate\Support\Str::uuid();
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Something went wrong. Please try again.', 'reference' => $reference], $response->getStatusCode());
            }
            return response()->view('errors.store-failure', ['reference' => $reference], $response->getStatusCode());
        }
        return $response;
    }
}
