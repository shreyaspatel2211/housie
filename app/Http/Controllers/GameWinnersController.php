<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Game;
use App\Models\WinnerHistory;
use App\Models\WinningAmount;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;

class GameWinnersController extends Controller
{
    // public function getWinners($game_id)
    // {
    //     $game = Game::find($game_id);
    //     if (!$game) {
    //         return response()->json(['message' => 'Game not found'], 404);
    //     }

    //     $fullHouseLimit = $game->winner_for_full_house;
    //     $otherLimit = $game->winner_for_other_categories;

    //     $winningAmount = WinningAmount::where('game_id', $game_id)->first();
    //     $amounts = $winningAmount ? json_decode($winningAmount->amount_json, true) : [];

    //     // Shared index for other category prize counter
    //     $otherPrizeIndex = 1;

    //     // Function to fetch category winners (for early_five, etc.)
    //     $fetchOtherCategoryWinners = function ($column) use ($game_id, &$otherPrizeIndex, $amounts, $otherLimit) {
    //         $winners = WinnerHistory::where('game_id', $game_id)
    //             ->where($column, 1)
    //             ->orderBy('created_at')
    //             ->limit($otherLimit)
    //             ->with('user:id,name,email,balance')
    //             ->get();

    //         foreach ($winners as $winner) {
    //             $key = "other_category_winners_" . $otherPrizeIndex;
    //             $value = $amounts[$key] ?? null;
                
    //             if (is_array($value)) {
    //                 $amount = isset($value[0]) ? (int) $value[0] : 0;
    //             } elseif (is_string($value)) {
    //                 $amount = (int) $value;
    //             } else {
    //                 $amount = 0;
    //             }

    //             $winner->prize_amount = $amount;
    //             $winner->user->balance = ($winner->user->balance ?? 0) + $amount;
    //             $winner->user->save();
    //             $otherPrizeIndex++;
    //         }

    //         return $winners;
    //     };

    //     // Full house winners (with their own amount keys)
    //     $fetchFullHouseWinners = function () use ($game_id, $amounts, $fullHouseLimit) {
    //         $winners = WinnerHistory::where('game_id', $game_id)
    //             ->where('full_house', 1)
    //             ->orderBy('created_at')
    //             ->limit($fullHouseLimit)
    //             ->with('user:id,name,email,balance')
    //             ->get();

    //         foreach ($winners as $index => $winner) {
    //             $key = "full_house_winners_" . ($index + 1);
    //             $amount = isset($amounts[$key][0]) ? (int) $amounts[$key][0] : 0;
    //             $winner->prize_amount = $amount;
    //             $winner->user->balance += $amount;
    //             $winner->user->save();
    //         }

    //         return $winners;
    //     };

    //     // Final structured response
    //     $winners = [
    //         'early_five' => $fetchOtherCategoryWinners('early_five'),
    //         'first_row' => $fetchOtherCategoryWinners('first_row'),
    //         'second_row' => $fetchOtherCategoryWinners('second_row'),
    //         'third_row' => $fetchOtherCategoryWinners('third_row'),
    //         'full_house' => $fetchFullHouseWinners(),
    //     ];

    //     return response()->json($winners);
    // }

    public function getWinners(Request $request, $game_id)
    {
        $token = $request->header('Authorization');

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token not provided'
            ], 401);
        }

        $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not authenticated'
            ], 401);
        }

        // Fetch all winners sorted by created_at for ranking
        $winners = WinnerHistory::where('game_id', $game_id)
                    ->where('user_id', $user->id)
                    ->with(['user', 'game'])
                    ->orderBy('created_at')
                    ->get();

        $categories = ['early_five', 'first_row', 'second_row', 'third_row', 'full_house'];

        foreach ($categories as $category) {
            // Filter winners who won in this category
            $categoryWinners = $winners->filter(function ($winner) use ($category) {
                return $winner->$category == 1 || $winner->$category === '1';
            })->values(); // reset keys

            if ($categoryWinners->count() >= 1) {
                foreach ($categoryWinners as $index => $winner) {
                    // Initialize ranks if not already set
                    $ranks = $winner->getAttribute('ranks') ?? [];
                    $ranks[$category] = $index + 1; // rank starts from 1
                    $winner->setAttribute('ranks', $ranks);
                }
            }
        }

        // Make sure ranks exists for all (even if empty)
        foreach ($winners as $winner) {
            if (!$winner->getAttribute('ranks')) {
                $winner->setAttribute('ranks', (object)[]);
            }
        }

        $winningAmounts = WinningAmount::where('game_id', $game_id)->first();
        $amountData = $winningAmounts ? json_decode($winningAmounts->amount_json, true) : null;

        return response()->json([
            'status' => true,
            'data' => $winners,
            'winning_amounts' => $amountData
        ]);
    }



}
