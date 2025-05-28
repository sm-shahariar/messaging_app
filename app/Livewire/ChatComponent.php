<?php

namespace App\Livewire;

use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ChatComponent extends Component
{
    public $messages = [];
    public $message = '';
    public $users = [];
    public $userId;
    public $receiver_id;
    public $conversations = [];

    public function mount()
    {
        try {
            $this->userId = auth()->id();
            $this->users = User::where('id', '!=', $this->userId)->get();

            $this->loadConversations();
            $this->messages = [];
        } catch (\Exception $e) {
            \Log::error('Error in mount: ' . $e->getMessage());
            $this->users = [];
            $this->messages = [];
            $this->conversations = [];
        }
    }

    public function loadConversations()
    {
        $userId = $this->userId;

        $this->conversations = User::whereIn('id', function ($query) use ($userId) {
            $query->select('sender_id')
                ->from('messages')
                ->where('receiver_id', $userId)
                ->union(
                    \DB::table('messages')
                        ->select('receiver_id')
                        ->where('sender_id', $userId)
                );
        })->get()->map(function ($user) use ($userId) {
            $lastMessage = Message::where(function ($q) use ($userId, $user) {
                $q->where('sender_id', $userId)->where('receiver_id', $user->id);
            })->orWhere(function ($q) use ($userId, $user) {
                $q->where('sender_id', $user->id)->where('receiver_id', $userId);
            })->latest()->first();

            return [
                'id' => $user->id,
                'name' => $user->name,
                'last_message' => $lastMessage ? $lastMessage->message : '',
                'last_message_time' => $lastMessage ? $lastMessage->created_at->diffForHumans() : '',
            ];
        })->toArray();
    }

    public function updateReceiver($id)
    {
        $this->receiver_id = $id;
        $this->loadConversation();
    }

    public function loadConversation()
    {
        if (!$this->receiver_id) {
            $this->messages = [];
            return;
        }

        $userId = $this->userId;
        $receiverId = $this->receiver_id;

        $this->messages = Message::where(function ($query) use ($userId, $receiverId) {
            $query->where('sender_id', $userId)->where('receiver_id', $receiverId);
        })->orWhere(function ($query) use ($userId, $receiverId) {
            $query->where('sender_id', $receiverId)->where('receiver_id', $userId);
        })->with('user')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'message' => $message->message,
                    'user' => [
                        'id' => $message->user->id,
                        'name' => $message->user->name,
                    ],
                    'created_at' => $message->created_at,
                ];
            })->toArray();
    }

    public function sendMessage()
    {
        $this->validate([
            'message' => 'required|string|max:255',
        ]);

        $messageContent = $this->message;
        
        

       $newMessage = Message::create([
        'sender_id' => $this->userId,
        'receiver_id' => $this->receiver_id,
        'message' => $messageContent,
        'created_at' => now(),
    ]);

        $this->messages[] = [
            'id' => $newMessage->id,
            'message' => $messageContent,
            'user' => [
                'id' => $this->userId,
                'name' => auth()->user()->name,
            ],
            'created_at' => $newMessage->created_at,
        ];

        // Refresh sidebar conversations
        $this->loadConversations();
        $this->loadConversation();
        
        $this->dispatch('scroll-chat');
    }

    public function getListeners()
    {
        return [
            "echo-private:dashboard.{$this->userId},MessageSent" => 'addMessage',
        ];
    }

    public function addMessage($event)
    {
        // Add message only if it belongs to the current conversation
        if (
            ($event['message']['sender_id'] == $this->receiver_id && $event['message']['receiver_id'] == $this->userId) ||
            ($event['message']['sender_id'] == $this->userId && $event['message']['receiver_id'] == $this->receiver_id)
        ) {
            // Check if message already exists to avoid duplicates
            $exists = collect($this->messages)->contains('id', $event['message']['id']);
            
            if (!$exists) {
                $this->messages[] = [
                    'id' => $event['message']['id'],
                    'message' => $event['message']['message'],
                    'user' => [
                        'id' => $event['user']['id'],
                        'name' => $event['user']['name'],
                    ],
                    'created_at' => $event['message']['created_at'],
                ];
                $this->loadConversation();
            }
        }
        $this->loadConversation();
    }

    public function render()
    {
        return view('livewire.chat-component');
    }
}