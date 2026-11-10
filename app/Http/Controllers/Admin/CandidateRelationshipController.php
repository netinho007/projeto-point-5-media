<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidateTag;
use Illuminate\Http\Request;

class CandidateRelationshipController extends Controller
{
    public function toggleFavorite(Request $request, Candidate $candidate)
    {
        $user = $request->user();
        $exists = $user->favoriteCandidates()->where('candidates.id', $candidate->id)->exists();
        $exists ? $user->favoriteCandidates()->detach($candidate->id) : $user->favoriteCandidates()->attach($candidate->id);
        return back()->with('success', $exists ? 'Candidato removido dos favoritos.' : 'Candidato adicionado aos favoritos.');
    }

    public function addTag(Request $request, Candidate $candidate)
    {
        $data = $request->validate(['tag' => ['required','string','max:80']]);
        $tag = CandidateTag::firstOrCreate(['name' => trim($data['tag'])]);
        $candidate->tags()->syncWithoutDetaching([$tag->id]);
        return back()->with('success', 'Tag adicionada.');
    }

    public function removeTag(Candidate $candidate, CandidateTag $tag)
    {
        $candidate->tags()->detach($tag->id);
        return back()->with('success', 'Tag removida.');
    }
}
