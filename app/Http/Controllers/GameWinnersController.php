<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Game;
use App\Models\WinnerHistory;
use App\Models\WinningAmount;
use App\Models\User;

class GameWinnersController extends Controller
{
    public function getWinners($game_id)
    {
        $game = Game::find($game_id);
        if (!$game) {
            return response()->json(['message' => 'Game not found'], 404);
        }

        $fullHouseLimit = $game->winner_for_full_house;
        $otherLimit = $game->winner_for_other_categories;

        $winningAmount = WinningAmount::where('game_id', $game_id)->first();
        $amounts = $winningAmount ? json_decode($winningAmount->amount_json, true) : [];

        // Shared index for other category prize counter
        $otherPrizeIndex = 1;

        // Function to fetch category winners (for early_five, etc.)
        $fetchOtherCategoryWinners = function ($column) use ($game_id, &$otherPrizeIndex, $amounts, $otherLimit) {
            $winners = WinnerHistory::where('game_id', $game_id)
                ->where($column, 1)
                ->orderBy('created_at')
                ->limit($otherLimit)
                ->with('user:id,name,email,balance')
                ->get();

            foreach ($winners as $winner) {
                $key = "other_category_winners_" . $otherPrizeIndex;
                $value = $amounts[$key] ?? null;
                
                if (is_array($value)) {
                    $amount = isset($value[0]) ? (int) $value[0] : 0;
                } elseif (is_string($value)) {
                    $amount = (int) $value;
                } else {
                    $amount = 0;
                }

                $winner->prize_amount = $amount;
                $winner->user->balance = ($winner->user->balance ?? 0) + $amount;
                $winner->user->save();
                $otherPrizeIndex++;
            }

            return $winners;
        };

        // Full house winners (with their own amount keys)
        $fetchFullHouseWinners = function () use ($game_id, $amounts, $fullHouseLimit) {
            $winners = WinnerHistory::where('game_id', $game_id)
                ->where('full_house', 1)
                ->orderBy('created_at')
                ->limit($fullHouseLimit)
                ->with('user:id,name,email,balance')
                ->get();

            foreach ($winners as $index => $winner) {
                $key = "full_house_winners_" . ($index + 1);
                $amount = isset($amounts[$key][0]) ? (int) $amounts[$key][0] : 0;
                $winner->prize_amount = $amount;
                $winner->user->balance += $amount;
                $winner->user->save();
            }

            return $winners;
        };

        // Final structured response
        $winners = [
            'early_five' => $fetchOtherCategoryWinners('early_five'),
            'first_row' => $fetchOtherCategoryWinners('first_row'),
            'second_row' => $fetchOtherCategoryWinners('second_row'),
            'third_row' => $fetchOtherCategoryWinners('third_row'),
            'full_house' => $fetchFullHouseWinners(),
        ];

        return response()->json($winners);
    }

}
