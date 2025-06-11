<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\GameUser;

class TicketGenerator extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'game_id' => 'required|integer|exists:games,id',
        ]);

        $gameId = $request->game_id;

        $gameUsers = GameUser::where('game_id', $gameId)->get();

        $totalTickets = $gameUsers->sum('no_of_tickets');

        $tickets = [];
        foreach ($gameUsers as $gameUser) {
            for ($t = 0; $t < $gameUser->no_of_tickets; $t++) {
                $ticket = $this->generateTicket();

                $ticketData = [
                    'user_id' => $gameUser->user_id,
                    'game_id' => $gameUser->game_id
                ];

                foreach ($ticket as $pos => $value) {
                    $ticketData['pos_' . $pos] = $value;
                }

                $tickets[] = Ticket::create($ticketData);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Tickets generated successfully.',
            'total_tickets_generated' => $totalTickets,
            'tickets' => $tickets
        ]);
    }

    private function generateTicket()
    {
        $min = 1;
        $max = 9;
        $count = 3;
        $column = 9;
        $random = array_fill(1, 27, null);

        $firstRow = [1, 4, 7, 10, 13, 16, 19, 22, 25];
        $secondRow = [2, 5, 8, 11, 14, 17, 20, 23, 26];
        $thirdRow = [3, 6, 9, 12, 15, 18, 21, 24, 27];

        $finalTicket = [];

        $generateRandom = function ($min, $max, $finalTicket) use (&$generateRandom) {
            $value = rand($min, $max);
            if (!in_array($value, $finalTicket)) {
                return $value;
            } else {
                return $generateRandom($min, $max, $finalTicket);
            }
        };

        for ($i = 1; $i <= $column; $i++) {
            for ($j = 1; $j <= $count; $j++) {
                $value = $generateRandom($min, $max, $finalTicket);
                $finalTicket[] = $value;
            }
            $min = $max + 1;
            $max = $min + 9;
        }

        sort($finalTicket);

        $getRandomItems = function ($arr, $count) {
            shuffle($arr);
            return array_slice($arr, 0, $count);
        };

        $finalBlankPositions = array_merge(
            $getRandomItems($firstRow, 4),
            $getRandomItems($secondRow, 4),
            $getRandomItems($thirdRow, 4)
        );

        foreach ($finalTicket as $index => $value) {
            $position = $index + 1;
            if (in_array($position, $finalBlankPositions)) {
                $random[$position] = null;
            } else {
                $random[$position] = $value;
            }
        }

        return $random;
    }
}
