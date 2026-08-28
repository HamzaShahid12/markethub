<?php

namespace App\Http\Controllers\Customer;

use App\Actions\Chat\SendMessage;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ConversationController extends Controller
{
    public function index(Request $request): Response
    {
        $conversations = Conversation::where('customer_id', $request->user()->id)
            ->with('vendor:id,shop_name,slug,logo')
            ->withCount(['messages as unread_count' => fn ($q) => $q->whereNull('read_at')->where('sender_id', '!=', $request->user()->id)])
            ->orderByDesc('last_message_at')
            ->get();

        return Inertia::render('Customer/Messages/Index', [
            'conversations' => $conversations->map(fn (Conversation $c) => [
                'id' => $c->id,
                'vendor_name' => $c->vendor->shop_name,
                'vendor_slug' => $c->vendor->slug,
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

        return Inertia::render('Customer/Messages/Show', [
            'conversation' => [
                'id' => $conversation->id,
                'vendor_name' => $conversation->vendor->shop_name,
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

    /**
     * Starts (or resumes) a conversation with a vendor — the entry
     * point is the "Message this vendor" button on a product page.
     */
    public function start(Request $request, Vendor $vendor): RedirectResponse
    {
        $conversation = Conversation::firstOrCreate(
            ['customer_id' => $request->user()->id, 'vendor_id' => $vendor->id],
            ['last_message_at' => now()],
        );

        return to_route('customer.messages.show', $conversation);
    }

    public function sendMessage(Request $request, Conversation $conversation, SendMessage $sendMessage): RedirectResponse
    {
        $this->authorize('view', $conversation);

        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $sendMessage->execute($conversation, $request->user(), $data['body']);

        return back();
    }
}
