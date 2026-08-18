<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Throwable;

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
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        // إذا كان الطلب من API، أرجع JSON بدلاً من HTML
        if ($request->is('api/*') || $request->expectsJson()) {
            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => 'خطأ في التحقق من البيانات',
                    'errors' => $e->errors()
                ], 422);
            }

            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return response()->json([
                    'message' => 'غير مصرح (الرجاء تسجيل الدخول)',
                    'error' => 'Unauthenticated'
                ], 401);
            }

            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return response()->json([
                    'message' => 'ليس لديك صلاحية للوصول لهذا الإجراء',
                    'error' => 'Unauthorized'
                ], 403);
            }

            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return response()->json([
                    'message' => 'العنصر المطلوب غير موجود',
                    'error' => 'Not Found'
                ], 404);
            }

            $statusCode = $this->isHttpException($e) ? $e->getStatusCode() : 500;

            return response()->json([
                'message' => $e->getMessage() ?: 'حدث خطأ غير متوقع',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], $statusCode);
        }

        return parent::render($request, $e);
    }
}
