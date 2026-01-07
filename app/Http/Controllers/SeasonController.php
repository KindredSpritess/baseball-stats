<?php

namespace App\Http\Controllers;

use App\Models\Season;
use Illuminate\Http\Request;

class SeasonController extends Controller
{
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