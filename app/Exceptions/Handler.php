<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Exceptions\Domain\DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        // сюди можна додавати ексепшини, які не треба логувати
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     */
    public function report(Throwable $e): void
    {
        parent::report($e);
    }

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        /**
         * 1️⃣ ДОМЕННІ ВИНЯТКИ (наш рівень бізнес-логіки)
         * ------------------------------------------------
         * Кидаються з Action/Service, обробляються централізовано.
         */
        $this->renderable(function (DomainException $e) {
            $body = [
                'success' => false,
                'error' => [
                    'code'    => $e->code(),
                    'message' => $e->getMessage() ?: 'Помилка бізнес-логіки.',
                    'details' => $e->context(),
                ],
            ];

            if (config('app.debug')) {
                $body['error']['debug'] = [
                    'exception' => class_basename($e),
                    'trace' => substr($e->getTraceAsString(), 0, 1000),
                ];
            }

            return response()->json($body, $e->status());
        });

        /**
         * 2️⃣ НЕ ЗНАЙДЕНО МОДЕЛЬ (404)
         */
        $this->renderable(function (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code'    => 'not_found',
                    'message' => 'Ресурс не знайдено.',
                ],
            ], Response::HTTP_NOT_FOUND);
        });

        /**
         * 3️⃣ ПОМИЛКА ДОСТУПУ / POLICY (403)
         */
        $this->renderable(function (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code'    => 'forbidden',
                    'message' => 'У доступі відмовлено.',
                ],
            ], Response::HTTP_FORBIDDEN);
        });

        /**
         * 4️⃣ ПОМИЛКА ВАЛІДАЦІЇ (422)
         */
        $this->renderable(function (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code'    => 'validation_error',
                    'message' => 'Помилка валідації.',
                    'fields'  => $e->errors(),
                ],
            ], $e->status);
        });

        /**
         * 5️⃣ НЕОЧІКУВАНІ / ІНШІ ПОМИЛКИ (500)
         */
        $this->renderable(function (Throwable $e) {
            // базовий fallback — щоб не віддавати stacktrace на проді
            $body = [
                'success' => false,
                'error' => [
                    'code'    => 'server_error',
                    'message' => 'Внутрішня помилка сервера.',
                ],
            ];

            if (config('app.debug')) {
                $body['error']['message'] = $e->getMessage();
                $body['error']['exception'] = class_basename($e);
                $body['error']['trace'] = substr($e->getTraceAsString(), 0, 1000);
            }

            return response()->json($body, Response::HTTP_INTERNAL_SERVER_ERROR);
        });
    }
}
