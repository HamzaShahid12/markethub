<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PayoutController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString() ?: 'requested';

        $payouts = Payout::query()
            ->with('vendor:id,shop_name,payout_method,bank_name,account_title,account_number,iban,payout_phone')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Payouts/Index', [
            'payouts' => $payouts,
            'filters' => ['status' => $status],
            'counts' => [
                'requested' => Payout::where('status', 'requested')->count(),
                'approved' => Payout::where('status', 'approved')->count(),
                'paid' => Payout::where('status', 'paid')->count(),
                'all' => Payout::count(),
            ],
        ]);
    }

    public function approve(Payout $payout): RedirectResponse
    {
        if ($payout->status !== 'requested') {
            return back()->with('error', 'Only a requested payout can be approved.');
        }

        $payout->update(['status' => 'approved']);

        return back()->with('success', 'Payout approved.');
    }

    public function reject(Request $request, Payout $payout): RedirectResponse
    {
        if (in_array($payout->status, ['paid', 'rejected'], true)) {
            return back()->with('error', 'This payout has already been finalized.');
        }

        $data = $request->validate(['admin_note' => ['nullable', 'string', 'max:500']]);

        DB::transaction(function () use ($payout, $data) {
            $payout->update(['status' => 'rejected', 'admin_note' => $data['admin_note'] ?? null]);

            // Only release commissions still awaiting payout — never touch
            // one that's already been marked paid by ANY payout, which
            // would otherwise let the same money become "available" again.
            $payout->commissions()->where('status', 'payable')->update(['payout_id' => null]);
        });

        return back()->with('success', 'Payout rejected — unpaid commissions returned to the vendor\'s balance.');
    }

    public function markPaid(Request $request, Payout $payout): RedirectResponse
    {
        if ($payout->status !== 'approved') {
            return back()->with('error', 'Only an approved payout can be marked as paid.');
        }

        $data = $request->validate(['reference_number' => ['required', 'string', 'max:255']]);

        DB::transaction(function () use ($payout, $data) {
            $payout->update([
                'status' => 'paid',
                'reference_number' => $data['reference_number'],
                'processed_at' => now(),
            ]);

            $payout->commissions()->update(['status' => 'paid']);
        });

        return back()->with('success', 'Payout marked as paid.');
    }
}