<?php

namespace App\Livewire;

use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ChatComponent extends Component
{
    public $messages = [];
    public $message = '';

    public function mount()
    {
        try {
            $this->messages = Message::with('user')->get()->toArray();
            \Log::info('Messages loaded:', ['count' => count($this->messages)]);
        } catch (\Exception $e) {
            \Log::error('Error loading messages: ' . $e->getMessage());
            $this->messages = [];
        }
    }

    public function sendMessage()
    {
        $this->validate(['message' => 'required|string|max:255']);

        $response = app('App\Http\Controllers\ChatController')->sendMessage(
            new \Illuminate\Http\Request(['message' => $this->message])
        );

        $data = $response->getData()->message;

        $this->messages[] =
            [
                'id' => $data->id,
                'message' => $data->message,
                'user' => [
                    'id' => Auth::user()->id,
                    'name' => Auth::user()->name,
                ],
                'created_at' => $data->created_at,
            ];

        $this->message = '';
    }

    public function getListeners()
    {
        return [
            "echo-private:dashboard,MessageSent" => 'addMessage',
        ];
    }

    public function addMessage($event)
    {
        $this->messages[] = [
            'id' => $event['message']['id'],
            'message' => $event['message']['message'],
            'user' => [
                'id' => $event['user']['id'],
                'name' => $event['user']['name'],
            ],
            'created_at' => $event['message']['created_at'],
        ];
    }

    public function render()
    {
        return view('livewire.chat-component');
    }
}