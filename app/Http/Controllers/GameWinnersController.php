<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Game;
use App\Models\WinnerHistory;
use App\Models\WinningAmount;
use App\Models\User;

class GameWinnersController extends Controller
{
    public function getGameData(Request $request)
    {
        $game_id = $request->input('game_id');

        if (!$game_id) {
            return response()->json(['message' => 'game_id is required'], 400);
        }

        // rest of the code remains exactly the same, replacing $game_id variable usage with this one

        // Fetch game details
        $game = Game::select(
            'early_five',
            'first_row',
            'second_row',
            'third_row',
            'winner_for_full_house',
            'winner_for_other_categories',
            'ticket_price'
        )->find($game_id);

        if (!$game) {
            return response()->json(['message' => 'Game not found'], 404);
        }

        // Determine which categories are ON
        $activeCategories = [];
        if ($game->early_five == 1) $activeCategories[] = 'early_five';
        if ($game->first_row == 1) $activeCategories[] = 'first_row';
        if ($game->second_row == 1) $activeCategories[] = 'second_row';
        if ($game->third_row == 1) $activeCategories[] = 'third_row';

        // Fetch winning_amount data
        $winningAmount = WinningAmount::where('game_id', $game_id)->first();
        $fullHouseAmounts = [];
        $otherCategoryAmounts = [];

        if ($winningAmount) {
            $amountJson = json_decode($winningAmount->amount_json, true);

            // Get full house winner amounts
            if (isset($amountJson['full_house_winners']) && is_array($amountJson['full_house_winners'])) {
                $fullHouseAmounts = $amountJson['full_house_winners'];
            }

            // Get other category winner amounts
            if (isset($amountJson['other_category_winners']) && is_array($amountJson['other_category_winners'])) {
                $otherCategoryAmounts = $amountJson['other_category_winners'];
            }
        }

        // Fetch full_house winners limited by winner_for_full_house ordered by created_at ascending
        $fullHouseWinners = WinnerHistory::with('user:id,name')
            ->where('game_id', $game_id)
            ->where('full_house', 1)
            ->orderBy('created_at', 'asc')
            ->limit($game->winner_for_full_house)
            ->get()
            ->map(function ($wh) {
                return [
                    'user_id' => $wh->user_id,
                    'user_name' => $wh->user->name ?? null,
                    'ticket_id' => $wh->ticket_id,
                    'early_five' => $wh->early_five,
                    'first_row' => $wh->first_row,
                    'second_row' => $wh->second_row,
                    'third_row' => $wh->third_row,
                    'full_house' => $wh->full_house,
                    'created_at' => $wh->created_at,
                ];
            });

        // Fetch other category winners
        $query = WinnerHistory::with('user:id,name')
            ->where('game_id', $game_id)
            ->where(function ($q) use ($activeCategories) {
                foreach ($activeCategories as $cat) {
                    $q->orWhere($cat, 1);
                }
            })
            ->where('full_house', 0);

        $otherCategoryWinners = $query->orderBy('created_at', 'asc')
            ->limit($game->winner_for_other_categories)
            ->get()
            ->map(function ($wh) {
                return [
                    'user_id' => $wh->user_id,
                    'user_name' => $wh->user->name ?? null,
                    'ticket_id' => $wh->ticket_id,
                    'early_five' => $wh->early_five,
                    'first_row' => $wh->first_row,
                    'second_row' => $wh->second_row,
                    'third_row' => $wh->third_row,
                    'full_house' => $wh->full_house,
                    'created_at' => $wh->created_at,
                ];
            });

        // Compose and return the response
        return response()->json([
            'game' => [
                'early_five' => $game->early_five,
                'first_row' => $game->first_row,
                'second_row' => $game->second_row,
                'third_row' => $game->third_row,
                'winner_for_full_house' => $game->winner_for_full_house,
                'winner_for_other_categories' => $game->winner_for_other_categories,
                'ticket_price' => $game->ticket_price,
                'active_categories' => $activeCategories,
            ],
            'winning_amounts' => [
                'full_house_winners' => $fullHouseAmounts,
                'other_category_winners' => $otherCategoryAmounts,
            ],
            'winners' => [
                'full_house' => $fullHouseWinners,
                'other_categories' => $otherCategoryWinners,
            ]
        ]);
    }

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
