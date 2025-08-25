<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Game;
use App\Models\User;
use App\Events\GameFinished;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class EndGameJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $game;

    public function __construct(Game $game)
    {
        $this->game = $game;
    }

    public function handle(): void
    {
        // Re-fetch the game from the database to get the latest state
        $game = Game::find($this->game->id);

        // Call our new centralized method to finish the game
        if ($game) {
            $game->finishGame();
        }
    }
}
