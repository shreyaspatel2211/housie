<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Game;
use App\Events\NumberGenerated;

class AutoPushGameNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:numbers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push random game numbers every 5 seconds';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $games = Game::where('status', '1')->get();

        foreach ($games as $game) {
            $queue = json_decode($game->queue, true) ?? [];
            $all = range(1, 89);
            $remaining = array_values(array_diff($all, $queue));

            if (count($remaining) === 0) continue;

            shuffle($remaining);
            $nextNumber = $remaining[0];

            // Save to DB
            $queue[] = $nextNumber;
            $game->queue = json_encode($queue);
            $game->save();

            // Broadcast
            broadcast(new NumberGenerated($nextNumber, $game->id))->toOthers();

            $this->info("Pushed number {$nextNumber} for game {$game->id}");
        }
    }
}
