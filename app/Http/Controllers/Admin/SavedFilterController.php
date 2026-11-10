<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSavedFilter;
use Illuminate\Http\Request;

class SavedFilterController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required','string','max:100'], 'context' => ['required','in:candidates,talent_pool,applications'], 'filters' => ['required','array']]);
        $request->user()->adminSavedFilters()->create($data);
        return back()->with('success', 'Filtro salvo.');
    }

    public function destroy(Request $request, AdminSavedFilter $filter)
    {
        abort_unless($filter->user_id === $request->user()->id, 403);
        $filter->delete();
        return back()->with('success', 'Filtro removido.');
    }
}
