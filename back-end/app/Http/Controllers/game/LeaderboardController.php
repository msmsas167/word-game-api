<?php

namespace App\Http\Controllers\game;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function index()
    {
        // Get the top 50 players (this query is fine)
        $topPlayers = User::orderByDesc('points')
            ->take(50)
            ->get(['id', 'name', 'points']);

        // Get the current authenticated user's rank
        $currentUser = Auth::user();
        $currentUserRank = null;
        $currentUserData = null;

        if ($currentUser) {
            // --- THE FIX: Use a simpler, more compatible query ---
            // 1. Count how many users have more points than the current user.
            $higherRankedUsers = User::where('points', '>', $currentUser->points)->count();
            
            // 2. The user's rank is that count + 1.
            $currentUserRank = $higherRankedUsers + 1;
            
            $currentUserData = [
                'name' => $currentUser->name,
                'points' => $currentUser->points,
                'rank' => $currentUserRank,
            ];
        }

        return response()->json([
            'top_players' => $topPlayers,
            'current_user' => $currentUserData,
        ]);
    }
}
