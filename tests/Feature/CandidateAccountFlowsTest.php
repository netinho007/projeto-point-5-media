<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\CandidateDocument;
use App\Models\User;
use App\Models\Vacancy;
use App\Notifications\ApplicationReceivedNotification;
use App\Notifications\ResetPasswordPtNotification;
use App\Notifications\VerifyEmailPtNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CandidateAccountFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_registration_sends_email_verification_and_keeps_phone_optional(): void
    {
        Notification::fake();
        config([
            'services.twilio.sid' => null,
            'services.twilio.token' => null,
            'services.twilio.verify_sid' => null,
        ]);

        $response = $this->post(route('candidate.register.post'), [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => 'Senha123',
            'password_confirmation' => 'Senha123',
            'privacy_consent' => '1',
        ]);

        $user = User::where('email', 'maria@example.com')->firstOrFail();

        $response->assertRedirect(route('candidate.security'));
        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertDatabaseHas('candidates', [
            'user_id' => $user->id,
            'email' => 'maria@example.com',
            'phone' => null,
        ]);
        $this->assertNull($user->candidate->phone_verification_hash);
        $this->assertNull($user->candidate->phone_verification_expires_at);

        Notification::assertSentTo($user, VerifyEmailPtNotification::class);
    }

    public function test_candidate_must_confirm_email_before_using_portal(): void
    {
        $user = $this->candidateUser([
            'email' => 'pending@example.com',
            'email_verified_at' => null,
        ]);
        $user->candidate->update([
            'phone_verified_at' => null,
        ]);

        $this->actingAs($user)
            ->get(route('candidate.portal'))
            ->assertRedirect(route('candidate.security'));

        $this->actingAs($user)
            ->get(route('candidate.security'))
            ->assertOk();

        $user->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($user->fresh('candidate'))
            ->get(route('candidate.portal'))
            ->assertOk();
    }

    public function test_pending_candidate_login_goes_to_security_page(): void
    {
        $user = $this->candidateUser([
            'email' => 'pending-login@example.com',
            'email_verified_at' => null,
            'password' => Hash::make('Senha123'),
        ]);
        $user->candidate->update([
            'phone_verified_at' => null,
        ]);

        $this->post(route('candidate.login.post'), [
            'email' => 'pending-login@example.com',
            'password' => 'Senha123',
        ])->assertRedirect(route('candidate.security'));
    }

    public function test_verified_candidate_can_login_without_phone_verification(): void
    {
        $user = $this->candidateUser([
            'email' => 'verified-login@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('Senha123'),
        ]);
        $user->candidate->update([
            'phone_verified_at' => null,
        ]);

        $this->post(route('candidate.login.post'), [
            'email' => 'verified-login@example.com',
            'password' => 'Senha123',
        ])->assertRedirect(route('candidate.portal'));
    }

    public function test_candidate_can_request_and_complete_password_reset(): void
    {
        Notification::fake();

        $user = $this->candidateUser([
            'email' => 'reset@example.com',
            'password' => Hash::make('Senha123'),
        ]);

        $this->post(route('candidate.password.email'), [
            'email' => 'reset@example.com',
        ])->assertSessionHas('success');

        $token = null;
        Notification::assertSentTo(
            $user,
            ResetPasswordPtNotification::class,
            function (ResetPasswordPtNotification $notification) use (&$token): bool {
                $token = $notification->token;

                return $token !== '';
            }
        );

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'reset@example.com',
            'password' => 'NovaSenha123',
            'password_confirmation' => 'NovaSenha123',
        ])->assertRedirect(route('candidate.login'));

        $this->assertTrue(Hash::check('NovaSenha123', $user->fresh()->password));
    }

    public function test_candidate_can_request_password_reset_by_verified_phone(): void
    {
        config([
            'services.twilio.sid' => null,
            'services.twilio.token' => null,
            'services.twilio.verify_sid' => null,
            'services.twilio.sms_from' => null,
        ]);

        $user = $this->candidateUser([
            'email' => 'phone-reset@example.com',
            'password' => Hash::make('Senha123'),
        ]);
        $user->candidate->update([
            'phone' => '(11) 99999-9999',
            'phone_verified_at' => now(),
        ]);

        $response = $this->post(route('candidate.password.phone.send'), [
            'phone' => '11 99999-9999',
        ]);

        $response->assertSessionHas('success');
        $message = (string) $response->getSession()->get('success');
        preg_match('/código de recuperação (\d{6})/', $message, $matches);
        $this->assertNotEmpty($matches[1] ?? null);

        $this->withSession($response->getSession()->all())->post(route('candidate.password.phone.update'), [
            'code' => $matches[1],
            'password' => 'NovaSenha123',
            'password_confirmation' => 'NovaSenha123',
        ])->assertRedirect(route('candidate.login'));

        $this->assertTrue(Hash::check('NovaSenha123', $user->fresh()->password));
    }

    public function test_candidate_can_change_email_and_receive_new_verification(): void
    {
        Notification::fake();
        config([
            'services.twilio.sid' => null,
            'services.twilio.token' => null,
            'services.twilio.sms_from' => null,
        ]);

        $user = $this->candidateUser(['email' => 'old-email@example.com']);
        $user->candidate->update([
            'phone' => '(11) 99999-9999',
            'phone_verified_at' => now(),
        ]);

        $this->actingAs($user)->patch(route('candidate.email.change'), [
            'email' => 'new-email@example.com',
            'current_password' => 'password',
        ])->assertSessionHas('success');

        $fresh = $user->fresh('candidate');
        $this->assertSame('new-email@example.com', $fresh->email);
        $this->assertNull($fresh->email_verified_at);
        $this->assertSame('new-email@example.com', $fresh->candidate->email);
        Notification::assertSentTo($fresh, VerifyEmailPtNotification::class);
    }

    public function test_candidate_can_verify_email_with_signed_link(): void
    {
        $user = $this->candidateUser([
            'email' => 'verify@example.com',
            'email_verified_at' => null,
        ]);

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)
            ->get($url)
            ->assertRedirect(route('candidate.portal'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_candidate_can_verify_phone_in_local_mode(): void
    {
        config([
            'services.twilio.sid' => null,
            'services.twilio.token' => null,
            'services.twilio.verify_sid' => null,
        ]);

        $user = $this->candidateUser();
        $user->candidate->update(['phone_verified_at' => null]);

        $response = $this->actingAs($user)->post(route('candidate.phone.send'), [
            'phone' => '(11) 99999-9999',
        ]);

        $response->assertSessionHas('success');

        $message = (string) $response->getSession()->get('success');
        preg_match('/(\d{6})/', $message, $matches);

        $this->assertNotEmpty($matches[1] ?? null);

        $this->actingAs($user->fresh('candidate'))
            ->post(route('candidate.phone.verify'), ['code' => $matches[1]])
            ->assertSessionHas('success');

        $this->assertNotNull($user->candidate->fresh()->phone_verified_at);
    }

    public function test_candidate_can_apply_to_open_vacancy_with_resume(): void
    {
        Notification::fake();
        Storage::fake('local');

        $user = $this->candidateUser();
        $vacancy = Vacancy::create([
            'title' => 'Desenvolvedor PHP',
            'department' => 'Tecnologia',
            'location' => 'Sao Paulo',
            'description' => 'Atuar no desenvolvimento de sistemas internos.',
            'requirements' => 'PHP, Laravel, MySQL',
            'employment_type' => 'CLT',
            'status' => 'open',
            'work_mode' => 'hibrido',
            'published_at' => now()->subDay(),
            'closes_at' => now()->addMonth(),
            'referral_enabled' => true,
        ]);

        $response = $this->actingAs($user)->post(route('vacancies.apply', $vacancy), [
            'resume' => UploadedFile::fake()->create('curriculo.pdf', 24, 'application/pdf'),
            'cover_letter' => 'Tenho experiencia com Laravel.',
            'source' => 'site',
        ]);

        $application = Application::firstOrFail();

        $response->assertRedirect(route('candidate.applications.show', $application));
        Storage::disk('local')->assertExists($application->resume_path);
        $this->assertDatabaseHas('application_status_histories', [
            'application_id' => $application->id,
            'status' => 'received',
        ]);

        Notification::assertSentTo($user, ApplicationReceivedNotification::class);
    }

    public function test_candidate_cannot_download_another_candidate_document(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('candidatos/2/documentos/portfolio.pdf', 'conteudo');

        $owner = $this->candidateUser(['email' => 'owner@example.com']);
        $other = $this->candidateUser(['email' => 'other@example.com']);

        $document = CandidateDocument::create([
            'candidate_id' => $owner->candidate->id,
            'type' => 'portfolio',
            'title' => 'Portfolio',
            'file_path' => 'candidatos/2/documentos/portfolio.pdf',
            'original_name' => 'portfolio.pdf',
            'mime' => 'application/pdf',
            'size' => 8,
        ]);

        $this->actingAs($other)
            ->get(route('candidate.documents.download', $document))
            ->assertForbidden();
    }

    private function candidateUser(array $overrides = []): User
    {
        $user = User::factory()->create($overrides);

        $user->candidate()->create([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '(11) 99999-9999',
            'phone_verified_at' => now(),
            'privacy_consent_at' => now(),
            'contact_preferences' => ['email' => true, 'portal' => true, 'sms' => false, 'whatsapp' => false],
        ]);

        return $user->fresh('candidate');
    }
}
