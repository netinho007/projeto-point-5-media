<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_lockout_keeps_form_email_and_countdown_and_expires(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com', 'is_admin' => true]);
        $this->failLogin('admin', $admin->email, 5);
        $response = $this->post(route('admin.login.post'), ['email' => $admin->email, 'password' => 'password']);
        $response->assertRedirect(route('admin.login'))->assertSessionHasErrors('email')->assertHeader('Retry-After');
        $response->assertSessionHas('_old_input.email', $admin->email)->assertSessionMissing('_old_input.password');
        $this->assertGuest();
        $page = $this->get(route('admin.login'))->assertOk()->assertSee('Muitas tentativas de login')->assertSee('data-login-retry-at', false);
        $this->capture('admin-throttled', $page->getContent());
        $this->travel(61)->seconds();
        $this->post(route('admin.login.post'), ['email' => $admin->email, 'password' => 'password'])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_candidate_lockout_uses_same_friendly_form(): void
    {
        $this->failLogin('candidate', 'candidate@example.com', 5);
        $this->post(route('candidate.login.post'), ['email' => 'candidate@example.com', 'password' => 'wrong'])
            ->assertRedirect(route('candidate.login'))->assertSessionHas('login_retry_at');
        $page = $this->get(route('candidate.login'))->assertOk()->assertSee('Muitas tentativas de login')->assertSee('data-login-retry-at', false);
        $this->capture('candidate-throttled', $page->getContent());
    }

    public function test_candidate_and_old_recovery_limits_do_not_block_admin(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com', 'is_admin' => true]);
        $this->failLogin('candidate', $admin->email, 5);
        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit(sha1('|127.0.0.1'), 60);
        }
        $this->post(route('admin.login.post'), ['email' => 'ADMIN@EXAMPLE.COM', 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_one_account_lockout_does_not_block_another_account(): void
    {
        $admin = User::factory()->create(['email' => 'other@example.com', 'is_admin' => true]);
        $this->failLogin('admin', 'first@example.com', 5);
        $this->post(route('admin.login.post'), ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_success_resets_account_failures_and_valid_logins_are_not_counted(): void
    {
        $user = User::factory()->create(['email' => 'candidate@example.com']);
        $user->candidate()->create(['name' => $user->name, 'email' => $user->email, 'privacy_consent_at' => now()]);
        for ($cycle = 0; $cycle < 3; $cycle++) {
            $this->failLogin('candidate', $user->email, 4);
            $this->post(route('candidate.login.post'), ['email' => $user->email, 'password' => 'password'])
                ->assertRedirect(route('candidate.portal'));
            $this->post(route('candidate.logout'))->assertRedirect(route('vacancies.index'));
        }
        $this->assertGuest();
    }

    public function test_ip_limit_blocks_password_spraying_even_after_success(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com', 'is_admin' => true]);
        for ($i = 0; $i < 19; $i++) {
            $this->failLogin('admin', "account{$i}@example.com", 1);
        }
        $this->post(route('admin.login.post'), ['email' => $admin->email, 'password' => 'password'])->assertRedirect(route('admin.dashboard'));
        $this->post(route('admin.logout'));
        $this->failLogin('admin', 'last@example.com', 1);
        $this->postJson(route('admin.login.post'), ['email' => $admin->email, 'password' => 'password'])
            ->assertStatus(429)->assertHeader('Retry-After')->assertJsonStructure(['message', 'retry_after']);
        $this->assertGuest();
    }

    public function test_normalized_email_cannot_bypass_account_limit_and_json_has_wait_time(): void
    {
        $this->failLogin('admin', 'admin@example.com', 5);
        $this->postJson(route('admin.login.post'), ['email' => ' ADMIN@EXAMPLE.COM ', 'password' => 'wrong'])
            ->assertStatus(429)->assertJsonStructure(['message', 'retry_after']);
    }

    public function test_malformed_email_is_validation_error_not_server_error(): void
    {
        $this->postJson(route('admin.login.post'), ['email' => ['bad'], 'password' => 'wrong'])->assertUnprocessable();
    }

    public function test_wrong_role_attempts_are_also_limited(): void
    {
        $user = User::factory()->create(['email' => 'normal@example.com', 'is_admin' => false]);
        for ($i = 0; $i < 5; $i++) {
            $this->from(route('admin.login'))->post(route('admin.login.post'), ['email' => $user->email, 'password' => 'password'])
                ->assertSessionHasErrors('email');
            $this->assertGuest();
        }
        $this->postJson(route('admin.login.post'), ['email' => $user->email, 'password' => 'password'])->assertStatus(429);
    }

    private function failLogin(string $area, string $email, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->from(route($area.'.login'))->post(route($area.'.login.post'), ['email' => $email, 'password' => 'wrong'])
                ->assertRedirect(route($area.'.login'))->assertSessionHasErrors('email');
        }
    }

    private function capture(string $name, string $html): void
    {
        if (getenv('POINT_CAPTURE_UI') === '1') {
            File::ensureDirectoryExists(storage_path('app/qa'));
            File::put(storage_path('app/qa/'.$name.'.html'), $html);
        }
    }
}
