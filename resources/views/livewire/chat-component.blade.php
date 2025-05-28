<div class="container">
    <div class="card">
        <div class="card-header ms-4 mt-5 text-center text-white"
            style="font-size: large; background-color: rgb(17, 193, 193); padding: 10px 0px;">Chat</div>
        <div class="card-body ms-4 mt-5" style="height: 600px; overflow-y: scroll;">
            @foreach ($messages as $msg)
                <div class="mb-2">
                    <strong>{{ $msg['user']['name'] }}:</strong>
                    <span style="color: white; padding: 8px 12px; border-radius: 10px; background-color: black; display: inline-block; margin-top: 8px;
                        margin-bottom: 8px;">
                        {{ $msg['message'] }}
                    </span>
                    <small class="text-muted">{{ \Carbon\Carbon::parse($msg['created_at'])->diffForHumans() }}</small>
                </div>
            @endforeach
        </div>
        <div class="card-footer mb-4 ms-4">
            <form wire:submit.prevent="sendMessage">
                <div class="input-group">
                    <input type="text" wire:model="message" name="message" class="form-control"
                        placeholder="Type your message..."
                        style="width: 600px; border-radius: 10px; border: 1px solid rgb(17, 193, 193); padding: 10px 20px;">
                    <button type="submit"
                        style="background-color: rgb(17, 193, 193); border: none; border-radius: 10px; padding: 10px 20px; color: white; margin-left: 10px;">
                        Send
                    </button>
                </div>
                @error('message') <span class="text-danger" style="color: red;">
                    {{ $message }}
                </span> @enderror
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function () {
        if (typeof window.Echo === 'undefined') {
            console.error("Echo is not defined!");
            return;
        }

        window.Echo.private('dashboard')
            .listen('.MessageSent', (event) => {
                console.log("Message received!", event);
                addMessageToChat(event);
            });
    });

    function addMessageToChat(event) {
        const chatContainer = document.querySelector('.card-body');
        if (!chatContainer) return;

        const messageHtml = `
            <div class="mb-2" style="margin-top: 8px;">
                <strong>${event.user.name}:</strong>
                <span style="color: white; padding: 8px 12px; border-radius: 10px; background-color: black; display: inline-block; margin-top: 8px; margin-bottom: 8px;">
                    ${event.message.message}
                </span>
                <small class="text-muted">${new Date(event.message.created_at).toLocaleTimeString()}</small>
            </div>
        `;

        chatContainer.insertAdjacentHTML('beforeend', messageHtml);
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }
    </script>
@endpush