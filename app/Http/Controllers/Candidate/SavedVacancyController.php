<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Vacancy;
use Illuminate\Http\Request;

class SavedVacancyController extends Controller
{
    public function index(Request $request)
    {
        $candidate = $request->user()->candidate;
        $vacancies = $candidate->savedVacancies()->latest('saved_vacancies.created_at')->get();
        return view('candidate.portal.saved-vacancies', compact('candidate','vacancies'));
    }

    public function toggle(Request $request, Vacancy $vacancy)
    {
        $candidate = $request->user()->candidate;
        $exists = $candidate->savedVacancies()->where('vacancies.id', $vacancy->id)->exists();
        $exists ? $candidate->savedVacancies()->detach($vacancy->id) : $candidate->savedVacancies()->attach($vacancy->id);
        return back()->with('success', $exists ? 'Vaga removida das salvas.' : 'Vaga salva no seu perfil.');
    }
}
