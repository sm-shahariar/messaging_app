<div class="container mx-auto flex h-screen">
    <!-- Sidebar: Conversation List -->
    <div class="w-1/3 bg-gray-100 border-r border-gray-200 overflow-y-auto">
        <div class="p-4 border-b">
            <h2 class="text-lg font-semibold">Chats</h2>
        </div>
        <div>
            @if(count($conversations) > 0)
                @foreach ($conversations as $conversation)
                    <div wire:click="updateReceiver({{ $conversation['id'] }})"
                        class="p-4 hover:bg-gray-200 cursor-pointer flex items-center {{ $receiver_id == $conversation['id'] ? 'bg-gray-200' : '' }}">
                        <div class="flex-1">
                            <p class="font-medium">{{ $conversation['name'] }}</p>
                            <p class="text-sm text-gray-600 truncate">{{ $conversation['last_message'] }}</p>
                        </div>
                        <small class="text-xs text-gray-500">{{ $conversation['last_message_time'] }}</small>
                    </div>
                @endforeach
            @else
                {{-- Show all users if no conversations --}}
                @foreach ($users as $user)
                    <div wire:click="updateReceiver({{ $user->id }})"
                        class="p-4 hover:bg-gray-200 cursor-pointer flex items-center {{ $receiver_id == $user->id ? 'bg-gray-200' : '' }}">
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
            <div class="flex-1 p-4 overflow-y-auto bg-gray-50" x-ref="chatContainer" id="chatContainer">
                @foreach ($messages as $msg)
                    <div class="{{ $msg['user']['id'] == $userId ? 'flex justify-end' : 'flex justify-start' }} mb-4">
                        <div
                            class="{{ $msg['user']['id'] == $userId ? 'bg-teal-500 text-white' : 'bg-gray-200 text-gray-800' }} max-w-xs p-3 rounded-lg">
                            <p>{{ $msg['message'] }}</p>
                            <small class="text-xs {{ $msg['user']['id'] == $userId ? 'text-teal-100' : 'text-gray-500' }}">
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
                        <input type="text" 
                            wire:model="message"
                            wire:keydown.enter.prevent="sendMessage"
                            class="flex-1 p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
                            placeholder="Type a message..."
                            autocomplete="off">

                        <button type="submit" 
                            class="ml-2 bg-teal-500 text-white p-2 rounded-lg hover:bg-teal-600 disabled:opacity-50"
                            wire:loading.attr="disabled">
                            <span wire:loading.remove>Send</span>
                            <span wire:loading>Sending...</span>
                        </button>
                    </div>
                    @error('message') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
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
        document.addEventListener("DOMContentLoaded", function () {
            if (typeof window.Echo === 'undefined') {
                console.error("Echo is not defined!");
                return;
            }

            let userId = @json($userId);
            let receiverId = @json($receiver_id);

            // Function to scroll chat to bottom
            function scrollChatToBottom() {
                const chatContainer = document.getElementById('chatContainer');
                if (chatContainer) {
                    setTimeout(() => {
                        chatContainer.scrollTop = chatContainer.scrollHeight;
                    }, 100);
                }
            }

            // Listen for scroll-chat event from Livewire
            window.addEventListener('scroll-chat', scrollChatToBottom);

            window.Echo.private(`dashboard.${userId}`)
                .listen('.MessageSent', (event) => {
                    console.log("Message received!", event);

                    // Update receiverId dynamically (in case it changed)
                    receiverId = @this.get('receiver_id');

                    if (event.message.sender_id == receiverId || event.message.receiver_id == receiverId) {
                        const chatContainer = document.getElementById('chatContainer');
                        if (!chatContainer) return;

                        const isOwnMessage = event.user.id == userId;
                        const messageHtml = `
                            <div class="${isOwnMessage ? 'flex justify-end' : 'flex justify-start'} mb-4">
                                <div class="${isOwnMessage ? 'bg-teal-500 text-white' : 'bg-gray-200 text-gray-800'} max-w-xs p-3 rounded-lg">
                                    <p>${event.message.message}</p>
                                    <small class="text-xs ${isOwnMessage ? 'text-teal-100' : 'text-gray-500'}">
                                        ${new Date(event.message.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                    </small>
                                </div>
                            </div>
                        `;

                        chatContainer.insertAdjacentHTML('beforeend', messageHtml);
                        scrollChatToBottom();
                    }
                });
        });

        // Auto-scroll chat on Livewire update
        document.addEventListener('livewire:updated', function () {
            const chatContainer = document.getElementById('chatContainer');
            if (chatContainer) {
                setTimeout(() => {
                    chatContainer.scrollTop = chatContainer.scrollHeight;
                }, 100);
            }
        });

        // Clear input after sending message
        document.addEventListener('livewire:updated', function (event) {
            if (event.detail.component.name === 'chat-component') {
                const messageInput = document.querySelector('input[wire\\:model="message"]');
                if (messageInput && messageInput.value === '') {
                    messageInput.blur();
                    messageInput.focus();
                }
            }
        });
    </script>
@endpush