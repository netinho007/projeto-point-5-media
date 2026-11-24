<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationNote;
use App\Services\AdminActivityLogger;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function store(Request $request, Application $application)
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:3000'],
        ]);

        $note = $application->notes()->create([
            'user_id' => auth()->id(),
            'note' => $validated['note'],
        ]);

        AdminActivityLogger::log('application.note_created', $application, ['note_id' => $note->id]);

        return back()->with('success', 'Anotação interna adicionada.');
    }

    public function destroy(ApplicationNote $note)
    {
        $application = $note->application;
        $note->delete();
        AdminActivityLogger::log('application.note_deleted', $application);

        return back()->with('success', 'Anotação removida.');
    }
}
