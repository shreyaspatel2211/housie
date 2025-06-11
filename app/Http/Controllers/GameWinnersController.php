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
}
