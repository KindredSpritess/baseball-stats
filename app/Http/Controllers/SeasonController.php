<?php

namespace App\Http\Controllers;

use App\Models\Season;
use Illuminate\Http\Request;

class SeasonController extends Controller
{
    public function create() {
        return view('season.create');
    }

    public function store(Request $request) {
        $season = new Season($request->validate([
            'name' => 'required|string|max:100',
        ]));
        $season->save();
        return redirect()->route('team.create', ['season' => $season->id]);
    }

    public function preferences(Season $season) {
        return view('season.preferences', [
            'season' => $season,
        ]);
    }

    public function getPreferences(Season $season) {
        return response()->json([
            'preferences' => $season->scoring_rules ?? [],
        ]);
    }

    public function storePreferences(Request $request, Season $season) {
        $data = $request->validate([
            'preferences' => 'nullable|array',
        ]);
        $season->scoring_rules = $data['preferences'] ?? [];
        $season->save();
        return response()->json(['status' => 'success']);
    }
}