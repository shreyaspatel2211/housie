<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Http\JsonResponse;

use App\Models\Ticket;

class TicketController extends Controller
{
    public function index(): JsonResponse
    {
        $tickets = Ticket::all();
        return response()->json($tickets);
    }

    public function getTicketsByUser($userId): \Illuminate\Http\JsonResponse
    {
        $tickets = Ticket::where('user_id', $userId)->get();

        if ($tickets->isEmpty()) {
            return response()->json(['message' => 'No tickets found for this user'], 404);
        }

        return response()->json($tickets);
    }

    public function show($id): \Illuminate\Http\JsonResponse
    {
        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json(['error' => 'Ticket not found'], 404);
        }

        return response()->json($ticket);
    }
}
