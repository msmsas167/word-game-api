<?php

namespace App\Http\Controllers\game;

use App\Http\Controllers\Controller;
use App\Models\Theme;
use App\Models\Game;
use App\Events\WordFound;
use App\Events\GameFinished;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GameController extends Controller
{
    public function startGame()
    {
        // Get a random theme that has at least one word
        $theme = Theme::has('words')->inRandomOrder()->first();

        if (!$theme) {
            return response()->json(['message' => 'No themes available.'], 404);
        }

        // --- Get all words, then select a random subset ---
        $allWords = $theme->words()->pluck('text'); // Get all word strings
        $wordCount = min($allWords->count(), rand(3, 5)); // Decide how many words to pick
        $words = $allWords->shuffle()->take($wordCount)->all(); // Shuffle and take the words

        // Generate the unique letters needed for these words
        $allLetters = implode('', $words);
        $uniqueLetters = array_values(array_unique(str_split($allLetters)));

        // Group words by their length
        $wordsToFind = collect($words)->groupBy(function ($word) {
            return strlen($word);
        });

        // Return the complete game setup as JSON
        return response()->json([
            'theme' => $theme->name,
            'availableLetters' => $uniqueLetters,
            'wordsToFind' => $wordsToFind,
        ]);
    }

    /**
     * Handle a player submitting a word in an online game.
     */
    public function submitWord(Request $request, Game $game)
    {
        $request->validate(['word' => 'required|string|uppercase']);
        $word = $request->word;
        $user = Auth::user();

        // --- Validation (from before) ---
        if ($game->status !== 'active') {
            return response()->json(['message' => 'This game is not active.'], 403);
        }
        if (!$game->players->contains($user)) {
            return response()->json(['message' => 'You are not a player in this game.'], 403);
        }
        $foundWordsCollection = collect($game->found_words ?? []);
        if ($foundWordsCollection->contains('word', $word)) {
            return response()->json(['message' => 'This word has already been found.'], 422);
        }
        if (!in_array($word, $game->words_for_game ?? [])) {
            return response()->json(['message' => 'This word is not in the list for this theme.'], 422);
        }

        // --- Update Game State ---
        $foundWords = $game->found_words ?? [];
        $foundWords[] = [
            'word' => $word,
            'user_id' => $user->id,
        ];
        $game->found_words = $foundWords;
        $game->save(); // Save the new word first

        // Broadcast that a word was found
        broadcast(new WordFound($game, $user, $word))->toOthers();

        // --- Check for Win Condition ---
        $totalWordsForGame = count($game->words_for_game);
        if (count($foundWords) === $totalWordsForGame) {
            $game->finishGame();
        }

        return response()->json([
            'message' => 'Word submitted successfully!',
            'game' => $game,
        ]);
    }
}
