<?php

namespace App\Jobs;

use App\Models\Game;
use App\Events\NumberGenerated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AutoPushNumberJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $gameId;
    public $number;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($gameId, $number)
    {
        $this->gameId = $gameId;
        $this->number = $number;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $game = Game::find($this->gameId);
        if (!$game) return;

        $queue = json_decode($game->queue, true) ?? [];

        // Prevent duplicate
        if (in_array($this->number, $queue)) return;

        $queue[] = $this->number;
        $game->queue = json_encode($queue);
        $game->save();

        broadcast(new NumberGenerated($this->number, $this->gameId))->toOthers();
    }

}
