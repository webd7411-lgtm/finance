<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Product;
use App\Models\Party;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\ActivityLog;
use App\Models\Tank;
use App\Models\StationLocation;
use App\Models\Pump;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    /**
     * Display a listing of purchases
     */
    public function index(Request $request)
    {
        $query = Purchase::with(['supplier', 'product', 'paymentAccount', 'creator'])->latest('purchase_date')->latest('id');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('purchase_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('purchase_date', '<=', $request->to_date);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhere('vehicle_no', 'like', "%{$search}%")
                  ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('product', fn ($pq) => $pq->where('name', 'like', "%{$search}%"));
            });
        }

        $purchases = $query->paginate(15)->withQueryString();

        // Metrics for petrol pump purchases
        $totalPurchasesAmount = Purchase::sum('total_amount');
        $totalPaidAmount = Purchase::sum('paid_amount');
        $totalPayableDue = max(0, $totalPurchasesAmount - $totalPaidAmount);
        $totalPurchasesCount = Purchase::count();

        $suppliers = Party::where('type', 'supplier')->orderBy('name')->get(['id', 'name']);
        $products = Product::orderBy('name')->get(['id', 'name', 'unit', 'category']);

        return view('purchases.index', compact(
            'purchases',
            'totalPurchasesAmount',
            'totalPaidAmount',
            'totalPayableDue',
            'totalPurchasesCount',
            'suppliers',
            'products'
        ));
    }

    /**
     * Show the form for creating a new purchase
     */
    public function create(Request $request)
    {
        $suppliers = Party::where('type', 'supplier')->orderBy('name')->get(['id', 'name', 'phone', 'current_balance']);
        $products = Product::active()->orderBy('name')->get(['id', 'name', 'unit', 'purchase_price', 'sale_price', 'current_stock', 'category', 'is_fuel', 'tank_or_shelf']);
        $accounts = Account::active()->orderBy('type')->orderBy('name')->get(['id', 'name', 'type', 'current_balance']);
        $pumps = Pump::with(['product', 'tank'])->active()->orderBy('name')->get();
        $tanks = Tank::with(['product', 'location'])->active()->orderBy('tank_number')->get();
        $locations = StationLocation::active()->orderBy('name')->get();

        $selectedPumpId = $request->input('pump_id');
        $selectedTankId = $request->input('tank_id');
        $selectedProductId = $request->input('product_id');

        if ($selectedPumpId && empty($selectedProductId)) {
            $selPump = Pump::find($selectedPumpId);
            $selectedProductId = $selPump?->product_id;
            if (!$selectedTankId && $selPump?->tank_id) {
                $selectedTankId = $selPump->tank_id;
            }
        } elseif ($selectedTankId && empty($selectedProductId)) {
            $selTank = Tank::find($selectedTankId);
            $selectedProductId = $selTank?->product_id;
        }

        return view('purchases.create', compact(
            'suppliers',
            'products',
            'accounts',
            'pumps',
            'tanks',
            'locations',
            'selectedPumpId',
            'selectedTankId',
            'selectedProductId'
        ));
    }

    /**
     * Store a newly created purchase in storage
     */
    public function store(Request $request)
    {
        // Sanitize comma-separated numeric values
        foreach (['quantity', 'unit_price', 'total_amount', 'paid_amount'] as $field) {
            if ($request->has($field) && is_string($request->input($field))) {
                $clean = str_replace(',', '', trim($request->input($field)));
                $request->merge([$field => $clean === '' ? 0 : $clean]);
            }
        }

        $validated = $request->validate([
            'purchase_date' => ['required', 'date'],
            'supplier_id'   => ['required', 'integer', 'exists:parties,id'],
            'location_id'   => ['nullable', 'integer', 'exists:station_locations,id'],
            'product_id'    => ['required', 'integer', 'exists:products,id'],
            'tank_id'       => ['nullable', 'integer', 'exists:tanks,id'],
            'pump_id'       => ['nullable', 'integer', 'exists:pumps,id'],
            'tank_no'       => ['nullable', 'string', 'max:255'],
            'invoice_no'    => ['nullable', 'string', 'max:100'],
            'vehicle_no'    => ['nullable', 'string', 'max:100'],
            'quantity'      => ['required', 'numeric', 'gt:0'],
            'unit_price'    => ['required', 'numeric', 'min:0'],
            'paid_amount'   => ['nullable', 'numeric', 'min:0'],
            'payment_account_id' => ['nullable', 'required_if:paid_amount,gt:0', 'exists:accounts,id'],
            'notes'         => ['nullable', 'string', 'max:1000'],
        ], [
            'supplier_id.required' => 'Please select a supplier / OMC party.',
            'product_id.required' => 'Please select a fuel or lubricant product.',
            'quantity.gt' => 'Purchase quantity must be greater than zero.',
            'payment_account_id.required_if' => 'Please select a payment account when paying an amount.',
        ]);

        $quantity = (float) $validated['quantity'];
        $unitPrice = (float) $validated['unit_price'];
        $totalAmount = (float) ($quantity * $unitPrice);
        $paidAmount = (float) ($validated['paid_amount'] ?? 0);
        $paymentAccountId = !empty($validated['payment_account_id']) && $paidAmount > 0 ? (int) $validated['payment_account_id'] : null;

        // Auto determine tank and location
        $tankId = !empty($validated['tank_id']) ? (int) $validated['tank_id'] : null;
        $locationId = !empty($validated['location_id']) ? (int) $validated['location_id'] : null;
        $pumpId = !empty($validated['pump_id']) ? (int) $validated['pump_id'] : null;
        $tankNo = $validated['tank_no'] ?? null;

        if ($pumpId && empty($tankId)) {
            $pumpObj = Pump::find($pumpId);
            if ($pumpObj) {
                $tankId = $pumpObj->tank_id;
                $tankNo = $pumpObj->tank_no ?? $pumpObj->tank?->name;
                if (!$locationId && $pumpObj->location_id) {
                    $locationId = $pumpObj->location_id;
                }
            }
        }

        if ($tankId && empty($tankNo)) {
            $tankObj = Tank::find($tankId);
            if ($tankObj) {
                $tankNo = $tankObj->name;
                if (!$locationId && $tankObj->location_id) {
                    $locationId = $tankObj->location_id;
                }
            }
        }

        $purchase = DB::transaction(function () use ($validated, $quantity, $unitPrice, $totalAmount, $paidAmount, $paymentAccountId, $tankId, $pumpId, $locationId, $tankNo) {
            $purchase = Purchase::create([
                'purchase_date'      => $validated['purchase_date'],
                'invoice_no'         => $validated['invoice_no'] ?? null,
                'supplier_id'        => $validated['supplier_id'],
                'location_id'        => $locationId,
                'product_id'         => $validated['product_id'],
                'tank_id'            => $tankId,
                'pump_id'            => $pumpId,
                'tank_no'            => $tankNo,
                'vehicle_no'         => $validated['vehicle_no'] ?? null,
                'quantity'           => $quantity,
                'unit_price'         => $unitPrice,
                'total_amount'       => $totalAmount,
                'paid_amount'        => $paidAmount,
                'payment_account_id' => $paymentAccountId,
                'notes'              => $validated['notes'] ?? null,
                'created_by'         => auth()->id(),
            ]);

            // 1. Inventory Update: Automatically increment product's live stock
            $product = Product::lockForUpdate()->findOrFail($validated['product_id']);
            $product->current_stock += $quantity;
            if ($unitPrice > 0) {
                $product->purchase_price = $unitPrice;
            }
            $product->save();

            // 2. Storage Tank Update: Automatically increment selected tank's live stock
            if ($tankId) {
                $tank = Tank::lockForUpdate()->find($tankId);
                if ($tank) {
                    $tank->current_stock += $quantity;
                    $tank->save();
                }
            }

            // 2. Supplier Ledger: Update supplier balance (Payable increases by unpaid amount)
            $supplier = Party::lockForUpdate()->findOrFail($validated['supplier_id']);
            $unpaid = $totalAmount - $paidAmount;
            $supplier->current_balance += $unpaid;
            $supplier->save();

            // 3. Record Purchase Bill Transaction (Appears as Credit in Supplier Ledger)
            $billNo = $purchase->invoice_no ?: ('PO #' . $purchase->id);
            $billDescription = "Stock purchase: {$quantity} {$product->unit_label} of {$product->name}"
                . ($purchase->vehicle_no ? " (Bowser: {$purchase->vehicle_no})" : "")
                . ($purchase->notes ? " - {$purchase->notes}" : "");

            Transaction::create([
                'date' => $validated['purchase_date'],
                'type' => 'purchase_bill',
                'purchase_id' => $purchase->id,
                'party_id' => $supplier->id,
                'account_id' => null,
                'amount' => $totalAmount,
                'bill_no' => $billNo,
                'description' => $billDescription,
                'created_by' => auth()->id(),
            ]);

            // 4. Payment Account Update & Outgoing Payment Transaction (Appears as Debit in Supplier Ledger)
            if ($paidAmount > 0 && $paymentAccountId) {
                $account = Account::lockForUpdate()->findOrFail($paymentAccountId);
                $account->current_balance -= $paidAmount;
                $account->save();

                Transaction::create([
                    'date' => $validated['purchase_date'],
                    'type' => 'payment_out',
                    'purchase_id' => $purchase->id,
                    'party_id' => $supplier->id,
                    'account_id' => $account->id,
                    'amount' => $paidAmount,
                    'bill_no' => $billNo,
                    'description' => "Stock purchase payment for {$quantity} {$product->unit_label} of {$product->name}" . ($purchase->vehicle_no ? " (Bowser: {$purchase->vehicle_no})" : ""),
                    'created_by' => auth()->id(),
                ]);
            }

            // 4. Audit Log
            ActivityLog::log(
                action: 'created',
                module: 'purchases',
                details: "Recorded stock purchase: {$quantity} {$product->unit_label} of '{$product->name}' from '{$supplier->name}' (Total: Rs. " . number_format($totalAmount, 2) . ", Paid: Rs. " . number_format($paidAmount, 2) . ")"
            );

            return $purchase;
        });

        return redirect()->route('purchases.index')->with('success', "Purchase of {$quantity} units recorded and inventory stock updated successfully.");
    }

    /**
     * Display the specified purchase
     */
    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier', 'product', 'paymentAccount', 'creator']);
        return view('purchases.show', compact('purchase'));
    }

    /**
     * Remove the specified purchase from storage and reverse inventory/balances
     */
    public function destroy(Purchase $purchase)
    {
        DB::transaction(function () use ($purchase) {
            // 1. Reverse stock increment (Product and Storage Tank)
            $product = Product::lockForUpdate()->find($purchase->product_id);
            if ($product) {
                $product->current_stock = max(0, $product->current_stock - $purchase->quantity);
                $product->save();
            }

            if ($purchase->tank_id) {
                $tank = Tank::lockForUpdate()->find($purchase->tank_id);
                if ($tank) {
                    $tank->current_stock = max(0, $tank->current_stock - $purchase->quantity);
                    $tank->save();
                }
            }

            // 2. Reverse supplier balance
            $supplier = Party::lockForUpdate()->find($purchase->supplier_id);
            if ($supplier) {
                $unpaid = $purchase->total_amount - $purchase->paid_amount;
                $supplier->current_balance -= $unpaid;
                $supplier->save();
            }

            // 3. Reverse account payment
            if ($purchase->paid_amount > 0 && $purchase->payment_account_id) {
                $account = Account::lockForUpdate()->find($purchase->payment_account_id);
                if ($account) {
                    $account->current_balance += $purchase->paid_amount;
                    $account->save();
                }
            }

            // 4. Delete linked transactions (purchase bill & payment)
            Transaction::where('purchase_id', $purchase->id)->delete();

            $desc = "Reversed purchase #{$purchase->id} ({$purchase->quantity} units of " . ($product->name ?? 'Product') . ")";
            $purchase->delete();

            ActivityLog::log(
                action: 'deleted',
                module: 'purchases',
                details: $desc
            );
        });

        return redirect()->route('purchases.index')->with('success', 'Purchase record deleted and inventory stock reversed successfully.');
    }
}
