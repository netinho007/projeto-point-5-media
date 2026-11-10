<?php

namespace App\Http\Controllers;

use App\Models\Vacancy;
use Illuminate\Http\Request;

class VacancyController extends Controller
{
    public function index(Request $request)
    {
        $vacancies = Vacancy::open()
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim((string) $request->input('q'));
                $query->where(function ($sub) use ($term) {
                    $sub->where('title', 'like', "%{$term}%")
                        ->orWhere('department', 'like', "%{$term}%")
                        ->orWhere('location', 'like', "%{$term}%")
                        ->orWhere('requirements', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('department'), fn ($q) => $q->where('department', $request->department))
            ->when($request->filled('work_mode'), fn ($q) => $q->where('work_mode', $request->work_mode))
            ->when($request->filled('employment_type'), fn ($q) => $q->where('employment_type', $request->employment_type))
            ->when($request->filled('location'), fn ($q) => $q->where('location', 'like', '%'.$request->location.'%'))
            ->latest('published_at')
            ->get();

        $departments = Vacancy::open()->whereNotNull('department')->distinct()->orderBy('department')->pluck('department');
        $employmentTypes = Vacancy::open()->whereNotNull('employment_type')->distinct()->orderBy('employment_type')->pluck('employment_type');

        return view('vacancies.index', compact('vacancies','departments','employmentTypes'));
    }

    public function show(Vacancy $vacancy)
    {
        abort_unless(Vacancy::open()->whereKey($vacancy->id)->exists(), 404);

        $candidate = auth()->check() && ! auth()->user()->is_admin ? auth()->user()->candidate : null;
        $existingApplication = $candidate ? $candidate->applications()->where('vacancy_id', $vacancy->id)->first() : null;
        $isSaved = $candidate ? $candidate->savedVacancies()->where('vacancies.id', $vacancy->id)->exists() : false;
        $relatedVacancies = Vacancy::open()->whereKeyNot($vacancy->id)
            ->when($vacancy->department, fn ($q) => $q->where('department', $vacancy->department))
            ->limit(3)->get();

        return view('vacancies.show', compact('vacancy', 'candidate', 'existingApplication', 'isSaved', 'relatedVacancies'));
    }

    public function career() { return view('career'); }
    public function process() { return view('process'); }
    public function faq() { return view('faq'); }
    public function privacy() { return view('privacy'); }
}
