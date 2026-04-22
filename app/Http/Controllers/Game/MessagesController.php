<?php

namespace App\Http\Controllers\Game;

use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MessagesController extends GameController
{
    public function index(): View
    {
        $messages = $this->user->messages()
            ->orderByDesc('created_at')
            ->paginate(20);

        // Mark all as read
        $this->user->messages()->where('unread', true)->update(['unread' => false]);

        return view('game.messages', [
            'messages' => $messages,
        ]);
    }

    public function show(int $id): View
    {
        $message = $this->user->messages()->findOrFail($id);
        $message->update(['unread' => false]);

        return view('game.message', [
            'message' => $message,
        ]);
    }

    public function destroy(int $id): RedirectResponse
    {
        $message = $this->user->messages()->findOrFail($id);
        $message->delete();

        return redirect()->route('game.messages')
            ->with('success', 'Nachricht gelöscht.');
    }
}
