<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Events\GameFinished;
use App\Models\User;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'status',
        'duration_seconds',
        'theme_id',
        'found_words',
        'winner_id',
        'finished_at',
        'words_for_game',
    ];

    protected $casts = [
        'found_words' => 'array',
        'words_for_game' => 'array',
    ];

    /**
     * The players that belong to the game.
     */
    public function players()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * The theme for this game.
     */
    public function theme()
    {
        return $this->belongsTo(Theme::class);
    }

    /**
     * Finishes the game, determines the winner, awards points, and broadcasts the result.
     */
    public function finishGame(): void
    {
        // If the game is not active, do nothing.
        if ($this->status !== 'active') {
            return;
        }

        // Determine the winner based on who found the most words
        $foundWords = collect($this->found_words ?? []);
        $scores = $foundWords->groupBy('user_id')->map(fn ($words) => $words->count());
        $winnerId = null;

        if ($scores->isNotEmpty()) {
            $maxScore = $scores->max();
            $topPlayers = $scores->filter(fn ($score) => $score === $maxScore);

            // If there is only one player with the highest score, they win.
            if ($topPlayers->count() === 1) {
                $winnerId = $topPlayers->keys()->first();
                $winner = User::find($winnerId);
                if ($winner) {
                    $winner->increment('points', 50); // Award points
                }
            }
        }
        
        // Update the game's status
        $this->status = 'finished';
        $this->winner_id = $winnerId;
        $this->finished_at = now();
        $this->save();

        // Broadcast the GameFinished event to all players
        broadcast(new GameFinished($this->load('players')))->toOthers();
    }
}
