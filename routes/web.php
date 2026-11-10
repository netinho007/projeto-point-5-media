<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AnalysisController;
use App\Http\Controllers\Admin\ApplicationController as AdminApplicationController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\CandidateController as AdminCandidateController;
use App\Http\Controllers\Admin\CandidateRelationshipController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InterviewController;
use App\Http\Controllers\Admin\MessageController as AdminMessageController;
use App\Http\Controllers\Admin\MessageTemplateController;
use App\Http\Controllers\Admin\SavedFilterController;
use App\Http\Controllers\Admin\NoteController;
use App\Http\Controllers\Admin\TalentPoolController;
use App\Http\Controllers\Admin\VacancyController as AdminVacancyController;
use App\Http\Controllers\Candidate\ApplicationController as CandidateApplicationController;
use App\Http\Controllers\Candidate\AuthController as CandidateAuthController;
use App\Http\Controllers\Candidate\MediaController;
use App\Http\Controllers\Candidate\MessageController as CandidateMessageController;
use App\Http\Controllers\Candidate\PortalController;
use App\Http\Controllers\Candidate\ProfileSectionController;
use App\Http\Controllers\Candidate\SavedVacancyController;
use App\Http\Controllers\Candidate\SecurityController;
use App\Http\Controllers\VacancyController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;

Route::middleware('track.public')->group(function () {
    Route::get('/', [VacancyController::class, 'index'])->name('vacancies.index');
    Route::get('/vagas/{vacancy}', [VacancyController::class, 'show'])->name('vacancies.show');
    Route::get('/carreiras', [VacancyController::class, 'career'])->name('career');
    Route::get('/processo-seletivo', [VacancyController::class, 'process'])->name('process');
    Route::get('/faq', [VacancyController::class, 'faq'])->name('faq');
    Route::get('/privacidade', [VacancyController::class, 'privacy'])->name('privacy');
});

Route::get('/entrar', [CandidateAuthController::class, 'showLogin'])->middleware('track.public')->name('candidate.login');
Route::post('/entrar', [CandidateAuthController::class, 'login'])->name('candidate.login.post');
Route::get('/cadastro', [CandidateAuthController::class, 'showRegister'])->middleware('track.public')->name('candidate.register');
Route::post('/cadastro', [CandidateAuthController::class, 'register'])->name('candidate.register.post');
Route::get('/esqueci-a-senha', [CandidateAuthController::class, 'showForgotPassword'])->middleware('track.public')->name('candidate.password.request');
Route::post('/esqueci-a-senha', [CandidateAuthController::class, 'sendResetLink'])->name('candidate.password.email');
Route::get('/redefinir-senha/{token}', [CandidateAuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/redefinir-senha', [CandidateAuthController::class, 'resetPassword'])->name('password.update');

Route::middleware('auth')->get('/media/candidatos/{candidate}/foto', [MediaController::class, 'photo'])->name('candidate.profile.photo');

Route::middleware('candidate')->group(function () {
    Route::get('/vagas/{vacancy}/candidatar', [CandidateApplicationController::class, 'create'])->name('vacancies.apply.form');
    Route::post('/vagas/{vacancy}/candidatar', [CandidateApplicationController::class, 'store'])->name('vacancies.apply');
    Route::post('/vagas/{vacancy}/salvar', [SavedVacancyController::class, 'toggle'])->name('candidate.saved-vacancies.toggle');

    Route::prefix('portal')->name('candidate.')->group(function () {
        Route::get('/', [PortalController::class, 'index'])->name('portal');
        Route::get('/perfil', [PortalController::class, 'profile'])->name('profile');
        Route::patch('/perfil', [PortalController::class, 'updateProfile'])->name('profile.update');
        Route::delete('/perfil/foto', [PortalController::class, 'removePhoto'])->name('profile.photo.remove');
        Route::get('/curriculo', [PortalController::class, 'resume'])->name('resume');

        Route::post('/perfil/idiomas', [ProfileSectionController::class, 'languageStore'])->name('languages.store');
        Route::delete('/perfil/idiomas/{language}', [ProfileSectionController::class, 'languageDestroy'])->name('languages.destroy');
        Route::post('/perfil/experiencias', [ProfileSectionController::class, 'experienceStore'])->name('experiences.store');
        Route::delete('/perfil/experiencias/{experience}', [ProfileSectionController::class, 'experienceDestroy'])->name('experiences.destroy');
        Route::post('/perfil/formacoes', [ProfileSectionController::class, 'educationStore'])->name('educations.store');
        Route::delete('/perfil/formacoes/{education}', [ProfileSectionController::class, 'educationDestroy'])->name('educations.destroy');
        Route::post('/perfil/projetos', [ProfileSectionController::class, 'projectStore'])->name('projects.store');
        Route::delete('/perfil/projetos/{project}', [ProfileSectionController::class, 'projectDestroy'])->name('projects.destroy');
        Route::post('/perfil/certificacoes', [ProfileSectionController::class, 'certificationStore'])->name('certifications.store');
        Route::delete('/perfil/certificacoes/{certification}', [ProfileSectionController::class, 'certificationDestroy'])->name('certifications.destroy');
        Route::get('/perfil/certificacoes/{certification}/arquivo', [ProfileSectionController::class, 'certificationDownload'])->name('certifications.download');
        Route::post('/perfil/cursos', [ProfileSectionController::class, 'courseStore'])->name('courses.store');
        Route::delete('/perfil/cursos/{course}', [ProfileSectionController::class, 'courseDestroy'])->name('courses.destroy');
        Route::get('/perfil/cursos/{course}/certificado', [ProfileSectionController::class, 'courseDownload'])->name('courses.download');
        Route::patch('/perfil/competencias', [ProfileSectionController::class, 'skillsUpdate'])->name('skills.update');
        Route::post('/perfil/documentos', [ProfileSectionController::class, 'documentStore'])->name('documents.store');
        Route::get('/perfil/documentos/{document}/baixar', [ProfileSectionController::class, 'documentDownload'])->name('documents.download');
        Route::delete('/perfil/documentos/{document}', [ProfileSectionController::class, 'documentDestroy'])->name('documents.destroy');

        Route::get('/candidaturas/{application}', [CandidateApplicationController::class, 'show'])->name('applications.show');
        Route::patch('/candidaturas/{application}/retirar', [CandidateApplicationController::class, 'withdraw'])->name('applications.withdraw');
        Route::patch('/candidaturas/{application}/entrevista/responder', [CandidateApplicationController::class, 'respondInterview'])->name('applications.interview.respond');

        Route::get('/mensagens', [CandidateMessageController::class, 'index'])->name('messages.index');
        Route::post('/mensagens', [CandidateMessageController::class, 'store'])->name('messages.store');
        Route::get('/mensagens/{message}/anexo', [CandidateMessageController::class, 'attachment'])->name('messages.attachment');
        Route::get('/vagas-salvas', [SavedVacancyController::class, 'index'])->name('saved-vacancies.index');

        Route::get('/seguranca', [SecurityController::class, 'index'])->name('security');
        Route::post('/seguranca/telefone/enviar', [SecurityController::class, 'sendPhoneCode'])->middleware('throttle:3,10')->name('phone.send');
        Route::post('/seguranca/telefone/verificar', [SecurityController::class, 'verifyPhone'])->middleware('throttle:10,10')->name('phone.verify');
        Route::patch('/seguranca/preferencias', [SecurityController::class, 'preferences'])->name('communication.preferences');
        Route::patch('/seguranca/senha', [SecurityController::class, 'updatePassword'])->name('password.change');
        Route::patch('/seguranca/desativar', [SecurityController::class, 'deactivate'])->name('account.deactivate');
        Route::patch('/seguranca/excluir', [SecurityController::class, 'requestDeletion'])->name('account.delete-request');

        Route::get('/notificacoes', [PortalController::class, 'notifications'])->name('notifications');
        Route::patch('/notificacoes/ler-todas', [PortalController::class, 'markAllRead'])->name('notifications.read-all');
        Route::patch('/notificacoes/{notification}/ler', [PortalController::class, 'markNotificationRead'])->name('notifications.read');
        Route::post('/email/verificacao', [PortalController::class, 'resendVerification'])->middleware('throttle:6,1')->name('verification.send');
        Route::post('/sair', [CandidateAuthController::class, 'logout'])->name('logout');
    });

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect()->route('candidate.portal')->with('success', 'E-mail verificado com sucesso.');
    })->middleware('signed')->name('verification.verify');
});

Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.post');

Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::resource('vacancies', AdminVacancyController::class)->except(['show', 'destroy']);

    Route::get('/candidates', [AdminCandidateController::class, 'index'])->name('candidates.index');
    Route::get('/candidates/{candidate}', [AdminCandidateController::class, 'show'])->name('candidates.show');
    Route::get('/candidates/{candidate}/resume', [AdminCandidateController::class, 'resume'])->name('candidates.resume');
    Route::get('/candidates/{candidate}/documents/{document}', [AdminCandidateController::class, 'document'])->name('candidates.documents.download');
    Route::get('/candidates/{candidate}/certifications/{certification}', [AdminCandidateController::class, 'certificationFile'])->name('candidates.certifications.download');
    Route::get('/candidates/{candidate}/courses/{course}', [AdminCandidateController::class, 'courseFile'])->name('candidates.courses.download');
    Route::patch('/candidates/{candidate}/reativar', [AdminCandidateController::class, 'reactivate'])->name('candidates.reactivate');
    Route::patch('/candidates/{candidate}/solicitacao-exclusao/resolver', [AdminCandidateController::class, 'resolveDeletionRequest'])->name('candidates.deletion-request.resolve');
    Route::delete('/candidates/{candidate}/dados-pessoais', [AdminCandidateController::class, 'destroyPrivacyData'])->name('candidates.privacy-data.destroy');
    Route::post('/candidates/{candidate}/favorite', [CandidateRelationshipController::class, 'toggleFavorite'])->name('candidates.favorite');
    Route::post('/candidates/{candidate}/tags', [CandidateRelationshipController::class, 'addTag'])->name('candidates.tags.store');
    Route::delete('/candidates/{candidate}/tags/{tag}', [CandidateRelationshipController::class, 'removeTag'])->name('candidates.tags.destroy');

    Route::get('/messages', [AdminMessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{candidate}', [AdminMessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{candidate}', [AdminMessageController::class, 'store'])->name('messages.store');
    Route::get('/messages/attachment/{message}', [AdminMessageController::class, 'attachment'])->name('messages.attachment');
    Route::get('/message-templates', [MessageTemplateController::class, 'index'])->name('message-templates.index');
    Route::post('/message-templates', [MessageTemplateController::class, 'store'])->name('message-templates.store');
    Route::delete('/message-templates/{template}', [MessageTemplateController::class, 'destroy'])->name('message-templates.destroy');

    Route::get('/talent-pool', [TalentPoolController::class, 'index'])->name('talent-pool.index');
    Route::post('/saved-filters', [SavedFilterController::class, 'store'])->name('saved-filters.store');
    Route::delete('/saved-filters/{filter}', [SavedFilterController::class, 'destroy'])->name('saved-filters.destroy');
    Route::get('/interviews', [InterviewController::class, 'index'])->name('interviews.index');
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

    Route::get('/applications', [AdminApplicationController::class, 'index'])->name('applications.index');
    Route::get('/applications/{application}', [AdminApplicationController::class, 'show'])->name('applications.show');
    Route::patch('/applications/{application}/status', [AdminApplicationController::class, 'updateStatus'])->name('applications.status');
    Route::get('/applications/{application}/resume', [AdminApplicationController::class, 'resume'])->name('applications.resume');
    Route::get('/applications/{application}/resume/download', [AdminApplicationController::class, 'downloadResume'])->name('applications.resume.download');

    Route::post('/applications/{application}/interview', [InterviewController::class, 'store'])->name('applications.interview.store');
    Route::delete('/applications/{application}/interview', [InterviewController::class, 'destroy'])->name('applications.interview.destroy');
    Route::post('/applications/{application}/notes', [NoteController::class, 'store'])->name('applications.notes.store');
    Route::delete('/notes/{note}', [NoteController::class, 'destroy'])->name('applications.notes.destroy');

    Route::get('/analyses', [AnalysisController::class, 'index'])->name('analyses.index');
    Route::post('/analyses/{application}', [AnalysisController::class, 'run'])->name('analyses.run');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
});
