<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\CareerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OnboardingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::redirect('/', '/login');
Route::redirect('/home', '/dashboard');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
    Route::get('/register', [AuthController::class, 'createRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
    Route::view('/guest', 'guest.preview')->name('guest.preview');
});
Route::middleware(['auth', 'student', 'onboarding'])->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/onboarding/career-role', [OnboardingController::class, 'index'])->name('onboarding.index');
    Route::post('/onboarding/initial-assessment', [OnboardingController::class, 'startInitial'])->name('onboarding.initial.start');
    Route::post('/onboarding/career-role', [OnboardingController::class, 'choose'])->name('onboarding.choose');
    Route::get('/onboarding/assessment', [OnboardingController::class, 'assessment'])->name('onboarding.assessment');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/courses', [DashboardController::class, 'courses'])->name('courses');
    Route::get('/courses/{slug}', [DashboardController::class, 'course'])->name('courses.show');
    Route::get('/courses/{slug}/modules/{module}', [DashboardController::class, 'module'])->name('courses.modules.show');
    Route::post('/courses/{slug}/modules/{module}/complete', [DashboardController::class, 'completeModule'])->name('courses.modules.complete');
    Route::get('/careers', [CareerController::class, 'index'])->name('careers');
    Route::get('/careers/{slug}', [CareerController::class, 'show'])->name('careers.show');
    Route::post('/careers/{slug}/target', [CareerController::class, 'target'])->name('careers.target');
    Route::get('/career-profile', [DashboardController::class, 'careerProfile'])->name('career-profile');
    Route::post('/career-profile', [DashboardController::class, 'updateCareerProfile'])->name('career-profile.update');
    Route::get('/competencies', [DashboardController::class, 'profile'])->name('competencies');
    Route::get('/competencies/{competency}/evidence', [\App\Http\Controllers\CompetencyEvidenceController::class, 'show'])->whereNumber('competency')->name('competencies.evidence');
    Route::post('/competencies/{competency}/projects/{project}', [\App\Http\Controllers\CompetencyEvidenceController::class, 'storeProject'])->whereNumber(['competency', 'project'])->name('competencies.projects.store');
    Route::get('/assessments', [AssessmentController::class, 'index'])->name('assessments.index');
    Route::post('/assessments/{assessment}/start', [AssessmentController::class, 'start'])->name('assessments.start');
    Route::get('/assessments/attempts/{attempt}', [AssessmentController::class, 'take'])->name('assessments.take');
    Route::post('/assessments/attempts/{attempt}', [AssessmentController::class, 'submit'])->name('assessments.submit');
    Route::post('/assessments/attempts/{attempt}/draft', [AssessmentController::class, 'saveDraft'])->name('assessments.draft');
    Route::get('/assessments/attempts/{attempt}/result', [AssessmentController::class, 'result'])->name('assessments.result');
});
