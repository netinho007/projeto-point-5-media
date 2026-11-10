<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Skill;
use Illuminate\Http\Request;

class TalentPoolController extends Controller
{
    public function index(Request $request)
    {
        $query = Candidate::query()
            ->where('talent_pool', true)
            ->where(function ($q) { $q->whereNull('talent_pool_expires_at')->orWhere('talent_pool_expires_at','>=',now()); })
            ->with(['skills','languages','certifications','educations','tags','applications.vacancy'])
            ->withCount('applications');

        if ($request->filled('q')) {
            $term = trim((string) $request->q);
            $query->where(function ($sub) use ($term) {
                $sub->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('headline', 'like', "%{$term}%")
                    ->orWhere('preferred_area', 'like', "%{$term}%")
                    ->orWhereHas('skills', fn ($skills) => $skills->where('name', 'like', "%{$term}%"));
            });
        }

        if ($request->filled('skill_id')) {
            $query->whereHas('skills', fn ($skills) => $skills->whereKey($request->integer('skill_id')));
        }

        if ($request->filled('state')) {
            $query->where('state', mb_strtoupper((string) $request->state));
        }

        if ($request->filled('area')) {
            $query->where('preferred_area', 'like', '%'.trim((string) $request->area).'%');
        }

        if ($request->filled('language')) {
            $term = trim((string) $request->language);
            $query->whereHas('languages', fn ($languages) => $languages->where('language','like',"%{$term}%"));
        }

        if ($request->filled('work_mode')) {
            $query->where('work_mode', $request->work_mode);
        }

        if ($request->filled('certification')) {
            $term = trim((string) $request->certification);
            $query->whereHas('certifications', fn ($q) => $q->where('name','like',"%{$term}%")->orWhere('issuer','like',"%{$term}%"));
        }

        if ($request->filled('education')) {
            $term = trim((string) $request->education);
            $query->whereHas('educations', fn ($q) => $q->where('course','like',"%{$term}%")->orWhere('institution','like',"%{$term}%"));
        }

        if ($request->filled('availability')) {
            $query->where('availability', 'like', '%'.trim((string) $request->availability).'%');
        }

        if ($request->boolean('favorites')) {
            $query->whereHas('favoritedBy', fn ($users) => $users->where('users.id', auth()->id()));
        }

        $candidates = $query->latest('talent_pool_consent_at')->paginate(20)->withQueryString();
        $skills = Skill::orderBy('name')->get(['id', 'name']);
        $savedFilters = $request->user()->adminSavedFilters()->where('context','talent_pool')->latest()->get();

        return view('admin.talent-pool.index', compact('candidates', 'skills', 'savedFilters'));
    }
}
