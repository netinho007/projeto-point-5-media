<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use Illuminate\Http\Request;

class MessageTemplateController extends Controller
{
    public function index()
    {
        return view('admin.message-templates.index', ['templates' => MessageTemplate::latest()->get()]);
    }

    public function store(Request $request)
    {
        MessageTemplate::create($request->validate([
            'name' => ['required','string','max:255'], 'channel' => ['required','in:portal,email,whatsapp,sms'],
            'subject' => ['nullable','string','max:255'], 'body' => ['required','string','max:5000'],
        ]) + ['active' => true]);
        return back()->with('success', 'Modelo de mensagem criado.');
    }

    public function destroy(MessageTemplate $template)
    {
        $template->delete();
        return back()->with('success', 'Modelo removido.');
    }
}
