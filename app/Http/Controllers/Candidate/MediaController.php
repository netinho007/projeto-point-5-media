<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function photo(Request $request, Candidate $candidate)
    {
        $user = $request->user();
        abort_unless($user && ($user->is_admin || $user->candidate?->id === $candidate->id), 403);
        abort_unless($candidate->profile_photo_path && Storage::disk('local')->exists($candidate->profile_photo_path), 404);
        return response()->file(Storage::disk('local')->path($candidate->profile_photo_path));
    }
}
