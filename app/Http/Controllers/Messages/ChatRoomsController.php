<?php

namespace App\Http\Controllers\Messages;

use App\Http\Controllers\Controller;
use App\Models\ChatRoom;
use Illuminate\Http\Request;

class ChatRoomsController extends Controller
{
    public function index()
    {
        $chatRooms = ChatRoom::all();

        return response()->json($chatRooms);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'complaint_id' => 'required|exists:complaints,complain_num',
        ]);

        $user = $request->user();
        $chatRoom = ChatRoom::where('complaint_id', $validated['complaint_id'])->first();
        if (!$chatRoom) {
            $chatRoom = ChatRoom::create([
                'complaint_id' => $validated['complaint_id'],
                'agent_id' => $user->id,
            ]);
        }

        return response()->json($chatRoom, 201);
    }

    public function show($id)
    {
        $chatRoom = ChatRoom::find($id)->with('messages');
        return response()->json($chatRoom);
    }
}
