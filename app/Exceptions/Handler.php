<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
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
        $this->renderable(function (\Symfony\Component\HttpKernel\Exception\HttpException $exception, $request) {
            if ($exception->getStatusCode() !== 419) return null;
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sesi berakhir. Masuk kembali sebelum melanjutkan.'], 419);
            }
            \Illuminate\Support\Facades\Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            if ($request->routeIs('assessments.submit')) {
                $request->session()->put('url.intended', route('assessments.take', (int) $request->route('attempt')));
            }
            return redirect()->route('login')->with('session_notice', 'Sesi berakhir. Silakan masuk kembali. Draft assessment dapat dipulihkan di tab ini.');
        });
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
