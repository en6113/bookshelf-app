<?php

namespace App\Exceptions;

use App\Models\Book;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpFoundation\Response;
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
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e): Response
    {
        if ($request->is('api/*') && $e instanceof ModelNotFoundException) {
            $message = $e->getModel() === Book::class
                ? '指定された書籍が見つかりませんでした'
                : '指定されたデータが見つかりませんでした';

            return response()->json([
                'message' => $message,
                'error' => 'NOT_FOUND',
            ], 404);
        }

        return parent::render($request, $e);
    }
}
