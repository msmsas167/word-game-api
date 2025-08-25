<?php

use App\Models\Game;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// --- NEW: Authorize the private game channel ---
Broadcast::channel('game.{gameId}', function (User $user, int $gameId) {
    $game = Game::find($gameId);
    
    // Check if the user is a player in the game
    if ($game && $game->players->contains($user)) {
        // --- For presence channels, you MUST return an array of user data ---
        return [
            'id' => $user->id,
            'name' => $user->name
        ];
    }

    return false;
});
