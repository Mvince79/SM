<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * ✅ Fix: dùng Symfony Response thay vì Illuminate\Http\Response
     * để cover cả JsonResponse (vì JsonResponse extends Response)
     */
    public function render($request, Throwable $e): Response
    {
        if ($request->expectsJson()) {
            return $this->renderJsonResponse($request, $e);
        }

        return parent::render($request, $e);
    }

    protected function renderJsonResponse(Request $request, Throwable $e): JsonResponse
    {
        if ($e instanceof ValidationException) {
            return response()->json([
                'status' => 'validation_error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], $e->status);
        }

        if ($e instanceof HttpExceptionInterface) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage() ?: 'An error occurred',
                'code' => $e->getStatusCode(),
            ], $e->getStatusCode());
        }

        $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
        $statusCode = (is_numeric($statusCode) && $statusCode >= 100 && $statusCode < 600)
            ? (int) $statusCode : 500;

        $messages = [
            401 => ['status' => 'unauthorized', 'message' => 'Unauthenticated. Please provide a valid authentication token.'],
            403 => ['status' => 'forbidden', 'message' => 'Forbidden. You do not have permission to access this resource.'],
            404 => ['status' => 'not_found', 'message' => 'Resource not found.'],
            429 => ['status' => 'too_many_requests', 'message' => 'Too many requests. Please try again later.'],
        ];

        if (isset($messages[$statusCode])) {
            return response()->json($messages[$statusCode], $statusCode);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'An error occurred while processing your request.',
            'error' => config('app.debug') ? $e->getMessage() : null,
        ], $statusCode);
    }
}