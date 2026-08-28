<?php

namespace App\Http\Controllers\Vendor;

use App\Actions\Chat\SendMessage;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ConversationController extends Controller
{
    public function index(Request $request): Response
    {
        $vendor = $request->user()->vendor;

        $conversations = Conversation::where('vendor_id', $vendor->id)
            ->with('customer:id,name')
            ->withCount(['messages as unread_count' => fn ($q) => $q->whereNull('read_at')->where('sender_id', '!=', $request->user()->id)])
            ->orderByDesc('last_message_at')
            ->get();

        return Inertia::render('Vendor/Messages/Index', [
            'conversations' => $conversations->map(fn (Conversation $c) => [
                'id' => $c->id,
                'customer_name' => $c->customer->name,
                'last_message_at' => $c->last_message_at?->toIso8601String(),
                'unread_count' => $c->unread_count,
            ]),
        ]);
    }

    public function show(Request $request, Conversation $conversation): Response
    {
        $this->authorize('view', $conversation);

        $conversation->messages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $request->user()->id)
            ->update(['read_at' => now()]);

        return Inertia::render('Vendor/Messages/Show', [
            'conversation' => [
                'id' => $conversation->id,
                'customer_name' => $conversation->customer->name,
            ],
            'messages' => $conversation->messages()->with('sender:id,name')->orderBy('created_at')->get()->map(fn ($m) => [
                'id' => $m->id,
                'sender_id' => $m->sender_id,
                'sender_name' => $m->sender->name,
                'body' => $m->body,
                'created_at' => $m->created_at->toIso8601String(),
            ]),
            'currentUserId' => $request->user()->id,
        ]);
    }

    public function sendMessage(Request $request, Conversation $conversation, SendMessage $sendMessage): RedirectResponse
    {
        $this->authorize('view', $conversation);

        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $sendMessage->execute($conversation, $request->user(), $data['body']);

        return back();
    }
}
