<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function updatePreferences(Request $request)
    {
        $user = Auth::user();
        $preferences = $request->input('preferences', []);
        $user->preferences = $preferences;
        $user->save();
        return response()->json(['message' => 'Preferences updated successfully']);
    }
}
