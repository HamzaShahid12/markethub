<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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

        $hasActivePayout = $vendor->payouts()->whereIn('status', ['requested', 'approved'])->exists();

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
            'canRequest' => $payableBalance >= self::MINIMUM_PAYOUT && ! $hasActivePayout,
            'hasActivePayout' => $hasActivePayout,
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

        // Never allow a second payout while one is already in flight —
        // this is what let the same commissions get claimed twice.
        if ($vendor->payouts()->whereIn('status', ['requested', 'approved'])->exists()) {
            return back()->with('error', 'You already have a payout in progress.');
        }

        $payout = DB::transaction(function () use ($vendor) {
            // Lock the rows so a double-submit (two tabs, fast double-click)
            // can't select the same commissions into two different payouts.
            $commissions = $vendor->commissions()
                ->where('status', 'payable')
                ->whereNull('payout_id')
                ->lockForUpdate()
                ->get();

            $amount = (float) $commissions->sum('vendor_amount');

            if ($amount < self::MINIMUM_PAYOUT) {
                return null;
            }

            $payout = Payout::create([
                'vendor_id' => $vendor->id,
                'amount' => $amount,
                'status' => 'requested',
            ]);

            $commissions->each->update(['payout_id' => $payout->id]);

            return $payout;
        });

        if (! $payout) {
            return back()->with('error', 'Minimum payout amount is $'.self::MINIMUM_PAYOUT.'.');
        }

        return back()->with('success', 'Payout requested — the admin will review it shortly.');
    }
}