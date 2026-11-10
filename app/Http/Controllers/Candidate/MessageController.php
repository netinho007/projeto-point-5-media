<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\CandidateMessage;
use App\Models\CandidateNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $candidate = $request->user()->candidate;
        $candidate->messages()->where('sender_role', 'admin')->whereNull('read_at')->update(['read_at' => now()]);
        $messages = $candidate->messages()->with(['application.vacancy','sender'])->reorder('created_at')->get();
        return view('candidate.portal.messages', compact('candidate','messages'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['message' => ['required','string','max:4000'], 'application_id' => ['nullable','integer'], 'attachment' => ['nullable','file','mimes:pdf,doc,docx,jpg,jpeg,png,webp','max:8192']]);
        $candidate = $request->user()->candidate;
        if (filled($data['application_id'] ?? null)) {
            abort_unless($candidate->applications()->whereKey($data['application_id'])->exists(), 403);
        }
        $attachmentPath = null;
        $attachmentName = null;
        $attachmentMime = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $file->store('mensagens/candidato-'.$candidate->id, 'local');
            $attachmentName = $file->getClientOriginalName();
            $attachmentMime = $file->getMimeType();
        }
        $candidate->messages()->create([
            'application_id' => $data['application_id'] ?? null,
            'sender_user_id' => $request->user()->id,
            'sender_role' => 'candidate',
            'message' => $data['message'],
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'attachment_mime' => $attachmentMime,
        ]);
        CandidateNotification::create([
            'candidate_id' => $candidate->id, 'application_id' => $data['application_id'] ?? null,
            'type' => 'message', 'title' => 'Mensagem enviada', 'message' => 'Sua mensagem foi enviada para a equipe de recrutamento.', 'read_at' => now(),
        ]);
        return back()->with('success', 'Mensagem enviada ao recrutamento.');
    }

    public function attachment(Request $request, CandidateMessage $message)
    {
        abort_unless($message->candidate_id === $request->user()->candidate->id, 403);
        abort_unless($message->attachment_path && Storage::disk('local')->exists($message->attachment_path), 404);
        return Storage::disk('local')->download($message->attachment_path, $message->attachment_name ?: 'anexo');
    }
}
