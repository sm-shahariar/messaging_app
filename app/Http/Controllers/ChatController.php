<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    // public function index()
    // {
    //     $messages = Message::orderBy("created_at", "desc")->paginate(10);
    //     return view('livewire.chat-component', compact('messages'));
    // }

    // public function fetchMessages()
    // {
    //     return Message::with('user')->get();
    // }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string'
        ]);

        $user = Auth::user();

        $message = Message::create([
            'user_id' => $user->id,
            'message' => $request->input('message')
        ]);

    
        broadcast(new MessageSent($user, $message))->toOthers();
        

        return response()->json(['status' => 'Message Sent!', 'message' => $message]);
    }

}