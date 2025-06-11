<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Ticket;
use Tymon\JWTAuth\Facades\JWTAuth;

class TicketController extends Controller
{
    public function index(): JsonResponse
    {
        $tickets = Ticket::all();
        return response()->json($tickets);
    }

public function getTicketsByUser(): \Illuminate\Http\JsonResponse
{
    $user = JWTAuth::parseToken()->authenticate();

    if (!$user) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized'], 401);
    }

    $tickets = Ticket::where('user_id', $user->id)->get();

    if ($tickets->isEmpty()) {
        return response()->json([
            'status' => 'error',
            'message' => 'No tickets found for this user'], 404);
    }

    return response()->json($tickets);
}

    public function show($id): \Illuminate\Http\JsonResponse
    {
        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ticket not found'], 404);
        }

        return response()->json($ticket);
    }
}
