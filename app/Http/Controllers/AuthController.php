<?php
namespace App\Http\Controllers;

use App\Domains\Assessment\Services\OnboardingProgress;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View { return view('auth.login'); }

    public function createRegister(): View
    {
        return view('auth.register');
    }

    public function store(Request $request, OnboardingProgress $progress): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required']]);
        if (!Auth::attempt($credentials + ['is_active' => true, 'role' => 'student'], false)) {
            return back()->withErrors(['email' => 'Email atau kata sandi tidak sesuai.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        $studentId = (int) DB::table('students')->where('user_id', $request->user()->id)->value('id');

        if ($studentId && !$progress->hasCompletedInitialAssessment($studentId) && !$progress->isLegacyProfileReady($studentId)) {
            return redirect()->route('onboarding.index')->with('show_preassessment_prompt', true);
        }

        return redirect()->intended(route('dashboard'));
    }
    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'nim' => ['required', 'string', 'max:30', 'unique:students,nim'],
            'cohort_year' => ['required', 'integer', 'min:2000', 'max:'.(now()->year + 1)],
            'study_program' => ['required', 'string', 'max:100'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'student',
                'is_active' => true,
            ]);

            DB::table('students')->insert([
                'user_id' => $user->id,
                'nim' => $data['nim'],
                'cohort_year' => $data['cohort_year'],
                'study_program' => $data['study_program'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('onboarding.index')
            ->with('show_preassessment_prompt', true)
            ->with('success', 'Akun berhasil dibuat. Mulai dari pre-assessment agar pembelajaranmu punya arah yang jelas.');
    }
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
