<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PayoutController extends Controller
{
    private const MINIMUM_PAYOUT = 20;

    public function index(Request $request): Response
    {
        $vendor = $request->user()->vendor;

        $payableBalance = (float) $vendor->commissions()
            ->where('status', 'payable')
            ->whereNull('payout_id')
            ->sum('vendor_amount');

        $payouts = $vendor->payouts()->latest()->paginate(10)->through(fn (Payout $p) => [
            'id' => $p->id,
            'amount' => $p->amount,
            'status' => $p->status,
            'reference_number' => $p->reference_number,
            'requested_at' => $p->created_at->toDateString(),
            'processed_at' => $p->processed_at?->toDateString(),
        ]);

        return Inertia::render('Vendor/Payouts/Index', [
            'payableBalance' => $payableBalance,
            'minimumPayout' => self::MINIMUM_PAYOUT,
            'canRequest' => $payableBalance >= self::MINIMUM_PAYOUT,
            'payouts' => $payouts,
            'hasPayoutDetails' => (bool) $vendor->payout_method,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $vendor = $request->user()->vendor;

        if (! $vendor->payout_method) {
            return back()->with('error', 'Add your payout details in Store Profile first.');
        }

        $commissions = $vendor->commissions()->where('status', 'payable')->whereNull('payout_id')->get();
        $amount = (float) $commissions->sum('vendor_amount');

        if ($amount < self::MINIMUM_PAYOUT) {
            return back()->with('error', 'Minimum payout amount is $'.self::MINIMUM_PAYOUT.'.');
        }

        $payout = Payout::create([
            'vendor_id' => $vendor->id,
            'amount' => $amount,
            'status' => 'requested',
        ]);

        $commissions->each->update(['payout_id' => $payout->id]);

        return back()->with('success', 'Payout requested — the admin will review it shortly.');
    }
}