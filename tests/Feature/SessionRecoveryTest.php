<?php

namespace Tests\Feature;

use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SessionRecoveryTest extends TestCase
{
    public function test_expired_form_redirects_to_login_without_replaying_input(): void
    {
        Route::middleware('web')->post('/test-expired-form', function () {
            throw new TokenMismatchException();
        });
        $this->post('/test-expired-form', ['password' => 'must-not-be-flashed'])
            ->assertRedirect('/login')
            ->assertSessionHas('session_notice')
            ->assertSessionMissing('_old_input.password');
    }

    public function test_json_clients_keep_expired_session_status(): void
    {
        Route::middleware('web')->post('/test-expired-json', function () {
            throw new TokenMismatchException();
        });
        $this->postJson('/test-expired-json')->assertStatus(419)
            ->assertJson(['message' => 'Sesi berakhir. Masuk kembali sebelum melanjutkan.']);
    }
}
