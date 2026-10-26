<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request)
    {
        $activeTab = $request->get('tab', 'expense');
        if (!in_array($activeTab, ['expense', 'purchase'])) {
            $activeTab = 'expense';
        }

        $expenseCategories = ExpenseCategory::expense()->latest()->paginate(15, ['*'], 'expense_page')->withQueryString();
        $purchaseCategories = ExpenseCategory::purchase()->latest()->paginate(15, ['*'], 'purchase_page')->withQueryString();

        $expenseCount = ExpenseCategory::expense()->count();
        $purchaseCount = ExpenseCategory::purchase()->count();

        return view('expense_categories.index', compact(
            'expenseCategories',
            'purchaseCategories',
            'expenseCount',
            'purchaseCount',
            'activeTab'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('expense_categories', 'name')->where(fn ($q) => $q->where('type', $request->type)),
            ],
            'type' => ['required', Rule::in(['expense', 'purchase'])],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        ExpenseCategory::create($validated);

        $typeLabel = $validated['type'] === 'purchase' ? 'Purchase' : 'Expense';
        return redirect()->route('expense-categories.index', ['tab' => $validated['type']])
            ->with('success', "{$typeLabel} category created successfully.");
    }

    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $targetType = $request->input('type', $expenseCategory->type);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('expense_categories', 'name')
                    ->ignore($expenseCategory->id)
                    ->where(fn ($q) => $q->where('type', $targetType)),
            ],
            'type' => ['required', Rule::in(['expense', 'purchase'])],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $expenseCategory->update($validated);

        $typeLabel = $validated['type'] === 'purchase' ? 'Purchase' : 'Expense';
        return redirect()->route('expense-categories.index', ['tab' => $validated['type']])
            ->with('success', "{$typeLabel} category updated successfully.");
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        if ($expenseCategory->transactions()->exists() || $expenseCategory->shiftClosingPartyPayments()->exists()) {
            return back()->with('error', 'Cannot delete this category because it is linked to existing transactions or shift payments.');
        }

        $type = $expenseCategory->type;
        $expenseCategory->delete();

        return redirect()->route('expense-categories.index', ['tab' => $type])
            ->with('success', 'Category deleted successfully.');
    }
}
