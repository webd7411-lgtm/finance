<?php

namespace App\Http\Controllers;

use App\Models\FuelRateRevision;
use App\Models\Product;
use App\Models\Tank;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FuelRateRevisionController extends Controller
{
    public function index(Request $request)
    {
        $query = FuelRateRevision::with(['product', 'affectedTank', 'creator'])->latest('effective_date')->latest('id');

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('impact')) {
            if ($request->impact === 'gain') {
                $query->where('rate_difference', '>', 0);
            } elseif ($request->impact === 'loss') {
                $query->where('rate_difference', '<', 0);
            }
        }

        if ($request->filled('from_date')) {
            $query->whereDate('effective_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('effective_date', '<=', $request->to_date);
        }

        $revisions = $query->paginate(15)->withQueryString();

        // Metrics calculations
        $totalRevisions = FuelRateRevision::count();
        $totalGain = FuelRateRevision::where('rate_difference', '>', 0)->sum('stock_gain_loss_amount');
        $totalLoss = FuelRateRevision::where('rate_difference', '<', 0)->sum('stock_gain_loss_amount');
        $netImpact = $totalGain + $totalLoss; // loss is negative

        $fuelProducts = Product::active()->fuel()->orderBy('name')->get();

        return view('rate_revisions.index', compact(
            'revisions',
            'totalRevisions',
            'totalGain',
            'totalLoss',
            'netImpact',
            'fuelProducts'
        ));
    }

    public function create(Request $request)
    {
        $fuelProducts = Product::active()->fuel()->with('tanks')->orderBy('name')->get();
        $tanks = Tank::with(['product', 'location'])->where('status', 'active')->orderBy('tank_number')->get();
        $selectedProductId = $request->input('product_id');

        return view('rate_revisions.create', compact('fuelProducts', 'tanks', 'selectedProductId'));
    }

    public function store(Request $request)
    {
        $stripCommas = function ($val) {
            if (is_null($val) || $val === '') return $val;
            return is_string($val) ? str_replace(',', '', $val) : $val;
        };

        foreach (['old_sale_rate', 'new_sale_rate', 'old_purchase_rate', 'new_purchase_rate', 'stock_at_revision'] as $field) {
            if ($request->has($field)) {
                $request->merge([$field => $stripCommas($request->input($field))]);
            }
        }

        $validated = $request->validate([
            'product_id'         => ['required', 'integer', 'exists:products,id'],
            'affected_tank_id'   => ['nullable', 'integer', 'exists:tanks,id'],
            'effective_date'     => ['required', 'date'],
            'effective_time'     => ['required', 'string', 'max:20'],
            'old_sale_rate'      => ['required', 'numeric', 'min:0'],
            'new_sale_rate'      => ['required', 'numeric', 'min:0'],
            'old_purchase_rate'  => ['nullable', 'numeric', 'min:0'],
            'new_purchase_rate'  => ['nullable', 'numeric', 'min:0'],
            'stock_at_revision'  => ['nullable', 'numeric', 'min:0'],
            'notification_ref'   => ['nullable', 'string', 'max:255'],
            'remarks'            => ['nullable', 'string', 'max:1000'],
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $oldSale = (float) $validated['old_sale_rate'];
        $newSale = (float) $validated['new_sale_rate'];
        $difference = round($newSale - $oldSale, 2);

        // If stock at revision was not entered, auto-fetch from tank or product
        $stockLiters = (float) ($validated['stock_at_revision'] ?? 0);
        if ($stockLiters <= 0) {
            if (!empty($validated['affected_tank_id'])) {
                $tank = Tank::find($validated['affected_tank_id']);
                $stockLiters = (float) ($tank?->current_stock ?? 0);
            } else {
                $stockLiters = (float) ($product->current_stock ?? 0);
            }
        }

        $gainLossAmount = round($stockLiters * $difference, 2);

        DB::transaction(function () use ($validated, $product, $difference, $stockLiters, $gainLossAmount, $newSale) {
            FuelRateRevision::create([
                'product_id'             => $validated['product_id'],
                'affected_tank_id'       => $validated['affected_tank_id'] ?? null,
                'effective_date'         => $validated['effective_date'],
                'effective_time'         => $validated['effective_time'],
                'old_sale_rate'          => $validated['old_sale_rate'],
                'new_sale_rate'          => $newSale,
                'rate_difference'        => $difference,
                'old_purchase_rate'      => $validated['old_purchase_rate'] ?? null,
                'new_purchase_rate'      => $validated['new_purchase_rate'] ?? null,
                'stock_at_revision'      => $stockLiters,
                'stock_gain_loss_amount' => $gainLossAmount,
                'notification_ref'       => $validated['notification_ref'] ?? null,
                'remarks'                => $validated['remarks'] ?? null,
                'created_by'             => auth()->id(),
            ]);

            // Update product's active sale price
            $product->sale_price = $newSale;
            if (!empty($validated['new_purchase_rate'])) {
                $product->purchase_price = (float) $validated['new_purchase_rate'];
            }
            $product->save();

            $diffLabel = $difference >= 0 ? "+Rs. {$difference}" : "-Rs. " . abs($difference);
            $impactLabel = $gainLossAmount >= 0 ? "Gain: +Rs. " . number_format($gainLossAmount, 2) : "Loss: -Rs. " . number_format(abs($gainLossAmount), 2);

            ActivityLog::log(
                action: 'created',
                module: 'fuel_rate_revisions',
                details: "Fuel Rate Revision applied for '{$product->name}' at 12:00 AM Midnight ({$validated['effective_date']}): Rs. {$validated['old_sale_rate']} -> Rs. {$newSale} ({$diffLabel}/Ltr). Stock Volume: " . number_format($stockLiters, 2) . " Ltr, Net Stock {$impactLabel}."
            );
        });

        $msgType = $gainLossAmount >= 0 ? 'Stock Gain (Profit)' : 'Stock Devaluation (Loss)';
        $sign = $gainLossAmount >= 0 ? '+' : '';

        return redirect()->route('rate-revisions.index')->with(
            'success',
            "Fuel Rate Revision applied successfully! Effective rate for '{$product->name}' is now Rs. " . number_format($newSale, 2) . "/Ltr. {$msgType}: {$sign}Rs. " . number_format($gainLossAmount, 2) . " on " . number_format($stockLiters, 2) . " Liters."
        );
    }

    public function show(FuelRateRevision $rateRevision)
    {
        $rateRevision->load(['product', 'affectedTank.location', 'creator']);
        return view('rate_revisions.show', compact('rateRevision'));
    }

    public function destroy(FuelRateRevision $rateRevision)
    {
        $productName = $rateRevision->product?->name ?? 'Fuel';
        $rateRevision->delete();

        return redirect()->route('rate-revisions.index')->with('success', "Rate revision record for {$productName} deleted successfully.");
    }
}
