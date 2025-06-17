<div class="container mx-auto flex h-screen">
    <!-- Sidebar: Conversation List -->
    <div class="w-1/3 bg-black text-white border-r border-gray-200 overflow-y-auto">
        <div class="p-4 border-b">
            <h2 class="text-lg font-semibold">Chats</h2>
        </div>
        <div>
            @if (!empty($conversations) && count($conversations) > 0)
                @foreach ($conversations as $conversation)
                    <div wire:click="updateReceiver({{ $conversation['id'] }})"
                        class="p-4 hover:bg-gray-200 hover:text-black cursor-pointer flex items-center {{ $receiver_id == $conversation['id'] ? 'bg-gray-200' : '' }}">
                        <div class="flex-1">
                            <p class="font-medium">{{ $conversation['name'] }}</p>
                            <p class="text-sm text-gray-600 truncate">{{ $conversation['last_message'] }}</p>
                        </div>
                        <small class="text-xs text-gray-500">{{ $conversation['last_message_time'] }}</small>
                    </div>
                @endforeach
            @else
                @foreach ($users as $user)
                    <div wire:click="updateReceiver({{ $user->id }})"
                        class="p-4 hover:bg-gray-200 hover:text-black cursor-pointer flex items-center {{ $receiver_id == $user->id ? 'bg-gray-200' : '' }}">
                        <div class="flex-1">
                            <p class="font-medium">{{ $user->name }}</p>
                            <p class="text-sm text-gray-600 truncate">No messages yet.</p>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <!-- Chat Area -->
    <div class="w-2/3 flex flex-col">
        @if ($receiver_id)
            <!-- Chat Header -->
            <div class="p-4 bg-teal-500 text-white flex items-center">
                <h3 class="text-lg font-semibold">
                    {{ $users->firstWhere('id', $receiver_id)->name ?? 'User' }}
                </h3>
            </div>

            <!-- Messages -->
            <div class="flex-1 p-4 overflow-y-auto bg-gray-50" id="chatContainer"
                wire:key="chat-messages-{{ $receiver_id }}">
                @foreach ($messages as $msg)
                    <div class="{{ $msg['user']['id'] == $userId ? 'flex justify-end' : 'flex justify-start' }} mb-4">
                        <div
                            class="{{ $msg['user']['id'] == $userId ? 'bg-teal-500 text-white' : 'bg-gray-200 text-gray-800' }} max-w-xs p-3 rounded-lg">
                            <p>{{ $msg['message'] }}</p>
                            <small
                                class="text-xs {{ $msg['user']['id'] == $userId ? 'text-teal-100' : 'text-gray-500' }}">
                                {{ \Carbon\Carbon::parse($msg['created_at'])->format('h:i A') }}
                            </small>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Message Input -->
            <div class="p-4 bg-white border-t">
                <form wire:submit.prevent="sendMessage">
                    <div class="flex items-center">
                        <input type="text" id="messageInput" wire:model="message"
                            wire:keydown.enter.prevent="sendMessage"
                            class="flex-1 p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
                            placeholder="Type a message..." autocomplete="off">

                        <button type="submit"
                            class="ml-2 bg-teal-500 text-white p-2 rounded-lg hover:bg-teal-600 disabled:opacity-50"
                            wire:loading.attr="disabled">
                            <span wire:loading.remove>Send</span>
                            <span wire:loading>Sending...</span>
                        </button>
                    </div>
                    @error('message')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </form>
            </div>
        @else
            <div class="flex-1 flex items-center justify-center bg-gray-50">
                <p class="text-gray-500">Select a chat to start messaging.</p>
            </div>
        @endif
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Scroll to bottom function
            function scrollToBottom() {
                const chatContainer = document.getElementById('chatContainer');
                if (chatContainer) {
                    chatContainer.scrollTop = chatContainer.scrollHeight;
                }
            }

            // Initial scroll
            scrollToBottom();

            // Livewire hook for scrolling after update
            Livewire.hook('message.processed', () => {
                setTimeout(scrollToBottom, 50);
            });

            // Clear input when Livewire finishes processing
            Livewire.hook('message.processed', (message) => {
                const input = document.getElementById('messageInput');
                if (input && message.component.fingerprint.name === 'chat-component') {
                    input.value = '';
                }
            });

            // Alternative: Listen for the specific event
            Livewire.on('message-sent', () => {
                const input = document.getElementById('messageInput');
                if (input) {
                    input.value = '';
                }
            });

            // Echo setup if available
            if (typeof window.Echo !== 'undefined') {
                window.Echo.private(`dashboard.${@json($userId)}`)
                    .listen('.MessageSent', (event) => {
                        Livewire.dispatch('handleNewMessage', event);
                    });
            }
        });
    </script>
@endpush
