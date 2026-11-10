<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vacancy;
use App\Services\AdminActivityLogger;
use Illuminate\Http\Request;

class VacancyController extends Controller
{
    public function index(Request $request)
    {
        $query = Vacancy::query()->withCount('applications');

        if ($request->filled('q')) {
            $term = trim((string) $request->input('q'));
            $query->where(function ($sub) use ($term) {
                $sub->where('title', 'like', "%{$term}%")
                    ->orWhere('department', 'like', "%{$term}%")
                    ->orWhere('location', 'like', "%{$term}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $vacancies = $query->latest()->paginate(15)->withQueryString();

        return view('admin.vacancies.index', compact('vacancies'));
    }

    public function create()
    {
        return view('admin.vacancies.create');
    }

    public function store(Request $request)
    {
        $vacancy = Vacancy::create($this->validateVacancy($request));
        AdminActivityLogger::log('vacancy.created', $vacancy);

        return redirect()->route('admin.vacancies.index')->with('success', 'Vaga cadastrada com sucesso.');
    }

    public function edit(Vacancy $vacancy)
    {
        return view('admin.vacancies.edit', compact('vacancy'));
    }

    public function update(Request $request, Vacancy $vacancy)
    {
        $before = $vacancy->only(['title', 'status']);
        $vacancy->update($this->validateVacancy($request));
        AdminActivityLogger::log('vacancy.updated', $vacancy, ['before' => $before]);

        return redirect()->route('admin.vacancies.index')->with('success', 'Vaga atualizada com sucesso.');
    }

    private function validateVacancy(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'requirements' => ['nullable', 'string', 'max:10000'],
            'employment_type' => ['nullable', 'string', 'max:100'],
            'work_mode' => ['nullable','in:presencial,hibrido,remoto,flexivel'],
            'published_at' => ['nullable','date'],
            'closes_at' => ['nullable','date','after_or_equal:published_at'],
            'referral_enabled' => ['nullable','boolean'],
            'status' => ['required', 'in:draft,open,paused,closed'],
        ]);
        $data['referral_enabled'] = $request->boolean('referral_enabled');
        if ($data['status'] === 'open' && empty($data['published_at'])) $data['published_at'] = now();
        return $data;
    }
}
