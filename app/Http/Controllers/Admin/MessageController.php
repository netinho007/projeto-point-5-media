<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\CandidateMessage;
use App\Models\CandidateNotification;
use App\Models\MessageTemplate;
use App\Notifications\NewCandidateMessageNotification;
use App\Services\AdminActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $query = Candidate::query()->withCount(['messages as unread_messages_count' => fn ($q) => $q->where('sender_role', 'candidate')->whereNull('read_at')])
            ->whereHas('messages')
            ->with(['messages' => fn ($q) => $q->latest()->limit(1)]);
        if ($request->filled('q')) {
            $term = trim((string) $request->q);
            $query->where(fn ($q) => $q->where('name','like',"%{$term}%")->orWhere('email','like',"%{$term}%"));
        }
        $candidates = $query->orderByDesc('updated_at')->paginate(20)->withQueryString();
        return view('admin.messages.index', compact('candidates'));
    }

    public function show(Candidate $candidate)
    {
        $candidate->messages()->where('sender_role','candidate')->whereNull('read_at')->update(['read_at' => now()]);
        $messages = $candidate->messages()->with(['application.vacancy','sender'])->reorder('created_at')->get();
        $applications = $candidate->applications()->with('vacancy')->latest()->get();
        $templates = MessageTemplate::where('active', true)->orderBy('name')->get();
        return view('admin.messages.show', compact('candidate','messages','applications','templates'));
    }

    public function store(Request $request, Candidate $candidate)
    {
        $data = $request->validate([
            'subject' => ['nullable','string','max:255'], 'message' => ['required','string','max:4000'],
            'application_id' => ['nullable','integer'], 'send_email' => ['nullable','boolean'],
            'attachment' => ['nullable','file','mimes:pdf,doc,docx,jpg,jpeg,png,webp','max:8192'],
        ]);
        if (filled($data['application_id'] ?? null)) abort_unless($candidate->applications()->whereKey($data['application_id'])->exists(), 422);
        $attachmentPath = null;
        $attachmentName = null;
        $attachmentMime = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $file->store('mensagens/candidato-'.$candidate->id, 'local');
            $attachmentName = $file->getClientOriginalName();
            $attachmentMime = $file->getMimeType();
        }

        $message = $candidate->messages()->create([
            'application_id' => $data['application_id'] ?? null,
            'sender_user_id' => $request->user()->id,
            'sender_role' => 'admin',
            'subject' => $data['subject'] ?? null,
            'message' => $data['message'],
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'attachment_mime' => $attachmentMime,
        ]);
        CandidateNotification::create([
            'candidate_id' => $candidate->id, 'application_id' => $data['application_id'] ?? null,
            'type' => 'message', 'title' => $data['subject'] ?: 'Nova mensagem do recrutamento', 'message' => $data['message'],
        ]);
        if ($request->boolean('send_email') && $candidate->user && (($candidate->contact_preferences['email'] ?? true) !== false)) {
            try { $candidate->user->notify(new NewCandidateMessageNotification($message)); } catch (\Throwable $e) { report($e); }
        }
        AdminActivityLogger::log('candidate.message_sent', $candidate, ['application_id' => $data['application_id'] ?? null]);
        return back()->with('success', 'Mensagem enviada ao candidato.');
    }

    public function attachment(CandidateMessage $message)
    {
        abort_unless($message->attachment_path && Storage::disk('local')->exists($message->attachment_path), 404);
        return Storage::disk('local')->download($message->attachment_path, $message->attachment_name ?: 'anexo');
    }
}
