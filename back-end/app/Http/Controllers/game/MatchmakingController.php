<?php

namespace App\Http\Controllers\game;

use App\Http\Controllers\Controller;
use App\Events\MatchStarted;
use App\Models\Game;
use App\Models\Theme;
use App\Jobs\EndGameJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MatchmakingController extends Controller
{
    /**
     * Handle a request from a user to join a game.
     */
    public function join(Request $request)
    {
        $user = Auth::user();
        $game = null;
        $matchWasFound = false;

        // The database transaction ensures data integrity.
        DB::transaction(function () use ($user, &$game, &$matchWasFound) {
            // Find the first available waiting game that the user is not part of.
            $waitingGame = Game::where('status', 'waiting')
                ->whereDoesntHave('players', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->withCount('players') // Eager load the player count
                ->lockForUpdate()
                ->first();

            // Check if we found a game and if it has exactly one player.
            if ($waitingGame && $waitingGame->players_count === 1) {
                // --- A suitable game was found, join it ---
                $game = $waitingGame;
                $game->players()->attach($user->id);
                $game->status = 'active';
                $game->save();
                
                // Set a flag to indicate we need to broadcast an event
                $matchWasFound = true;

            } else {
                // --- No suitable game found, create a new one ---
                $theme = Theme::inRandomOrder()->first();

                // Select the random subset of words
                $allWords = $theme->words()->pluck('text');
                $wordCount = min($allWords->count(), rand(3, 5));
                $selectedWords = $allWords->shuffle()->take($wordCount); 

                // Calculate duration
                $totalCharacters = $selectedWords->sum(fn($word) => strlen($word));
                $duration = 20 + ($selectedWords->count() * 10) + ($totalCharacters * 8);
                
                $game = Game::create([
                    'status' => 'waiting',
                    'theme_id' => $theme->id,
                    'duration_seconds' => round($duration),
                    'words_for_game' => $selectedWords->all(), // <-- Store the selected words
                ]);
                $game->players()->attach($user->id);
            }
        });

        // Broadcast the event AFTER the transaction is complete
        if ($matchWasFound) {
            // We must reload the relations to get the most up-to-date data
            $game->load('players', 'theme');

            // Manually add the words to the theme object for the broadcast
            $game->theme->words_for_game = $game->words_for_game;

            broadcast(new MatchStarted($game));
            // --- Dispatch the job with a delay ---
            EndGameJob::dispatch($game)->delay(now()->addSeconds($game->duration_seconds));
        }

        // Return the HTTP response to the player who made the request
        return response()->json([
            'message' => $game->status === 'active' ? 'Match found!' : 'Waiting for another player...',
            'game' => $game->load('players'),
        ]);
    }
}
