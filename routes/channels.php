<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat', function ($user) {
    return auth()->check();
});

Broadcast::channel('dashboard', function ($user) {
    return auth()->check();
});