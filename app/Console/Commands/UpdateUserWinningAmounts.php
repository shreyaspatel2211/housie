<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WinningAmount;
use App\Models\WinnerHistory;
use App\Models\User;
use DB;
use Carbon\Carbon;
use App\Models\Game; 

class UpdateUserWinningAmounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:winner-amounts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update user balance and winning_amount based on winning_amounts and winner_histories';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $today = Carbon::today()->toDateString();

        $winningAmounts = WinningAmount::whereHas('game', function ($query) use ($today) {
            $query->whereDate('date', $today);
        })->get();

        foreach ($winningAmounts as $winning) {
            $amountJson = json_decode($winning->amount_json, true);
            $gameId = $winning->game_id;

            // Process Full House Winners
            $fullHouseWinners = WinnerHistory::where('game_id', $gameId)
                ->where('full_house', 1)
                ->orderBy('created_at')
                ->get();

            $this->assignWinnings($fullHouseWinners, $amountJson, 'full_house_winners');

            // Process Other Category Winners
            $otherCategoryWinners = WinnerHistory::where('game_id', $gameId)
                ->where(function ($query) {
                    $query->where('first_row', 1)
                          ->orWhere('second_row', 1)
                          ->orWhere('third_row', 1)
                          ->orWhere('early_five', 1);
                })
                ->orderBy('created_at')
                ->get();

            $this->assignWinnings($otherCategoryWinners, $amountJson, 'other_category_winners');
        }

        $this->info('User balances and winning_amounts updated successfully.');
    }

    protected function assignWinnings($winners, $amountJson, $type)
    {
        $index = 1;

        foreach ($winners as $winner) {
            $key = "{$type}_{$index}";
            $amount = isset($amountJson[$key][0]) ? (float)$amountJson[$key][0] : 0;

            if ($amount > 0) {
                // Update the user's balance and winning_amount
                User::where('id', $winner->user_id)->update([
                    'balance' => DB::raw("balance + {$amount}"),
                    'winning_amount' => DB::raw("winning_amount + {$amount}"),
                ]);
            }

            $index++;
        }
    }
}
