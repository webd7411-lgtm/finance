<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\Party;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of products with statistics and filters
     */
    public function index(Request $request)
    {
        $query = Product::query()->with('supplier');

        // Filter by Category
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // Filter by Status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        } else {
            // Default: show all or active
        }

        // Filter by Stock Status
        if ($request->filled('stock_status')) {
            if ($request->stock_status === 'low_stock') {
                $query->whereNotNull('min_stock_alert')
                      ->whereColumn('current_stock', '<=', 'min_stock_alert');
            } elseif ($request->stock_status === 'out_of_stock') {
                $query->where('current_stock', '<=', 0);
            } elseif ($request->stock_status === 'in_stock') {
                $query->where('current_stock', '>', 0);
            }
        }

        // Search by name, code, density/octane, tank/shelf
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('density_or_octane', 'like', "%{$search}%")
                  ->orWhere('tank_or_shelf', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sort = $request->input('sort', 'latest');
        match ($sort) {
            'name_asc'   => $query->orderBy('name', 'asc'),
            'name_desc'  => $query->orderBy('name', 'desc'),
            'price_asc'  => $query->orderBy('sale_price', 'asc'),
            'price_desc' => $query->orderBy('sale_price', 'desc'),
            'stock_asc'  => $query->orderBy('current_stock', 'asc'),
            'stock_desc' => $query->orderBy('current_stock', 'desc'),
            default      => $query->latest(),
        };

        $products = $query->paginate(15)->withQueryString();

        // Key Petrol Station Inventory Metrics
        $totalProducts = Product::count();
        $totalFuel = Product::fuel()->count();
        $totalLubricants = Product::lubricants()->count();
        $totalLowStock = Product::lowStock()->count();
        $totalInventoryValue = Product::where('status', 'active')
            ->selectRaw('SUM(current_stock * purchase_price) as total_val')
            ->value('total_val') ?? 0;

        return view('products.index', compact(
            'products',
            'totalProducts',
            'totalFuel',
            'totalLubricants',
            'totalLowStock',
            'totalInventoryValue'
        ));
    }

    /**
     * Show the form for creating a new product
     */
    public function create()
    {
        $suppliers = Party::where('type', 'supplier')->orderBy('name')->get(['id', 'name', 'phone']);
        
        // Recommended presets tailored specifically for petrol pumps
        $petrolPumpPresets = [
            [
                'name' => 'Super Petrol (RON 92)',
                'code' => 'PET-92',
                'category' => 'fuel',
                'unit' => 'liter',
                'is_fuel' => true,
                'density_or_octane' => 'RON 92 / 0.740 kg/L',
                'tank_or_shelf' => 'Underground Tank #1',
                'min_stock_alert' => 2000,
                'description' => 'Unleaded Super Motor Gasoline (PMS) 92 RON fuel for light vehicles and motorbikes.'
            ],
            [
                'name' => 'High Speed Diesel (HSD)',
                'code' => 'HSD-01',
                'category' => 'fuel',
                'unit' => 'liter',
                'is_fuel' => true,
                'density_or_octane' => '0.835 kg/L / Euro 5',
                'tank_or_shelf' => 'Underground Tank #2',
                'min_stock_alert' => 3000,
                'description' => 'High Speed Diesel (HSD) for heavy commercial transports, buses, trucks, and diesel cars.'
            ],
            [
                'name' => 'Hi-Octane 97 (HOBC)',
                'code' => 'HOBC-97',
                'category' => 'fuel',
                'unit' => 'liter',
                'is_fuel' => true,
                'density_or_octane' => 'RON 97 / 0.755 kg/L',
                'tank_or_shelf' => 'Underground Tank #3',
                'min_stock_alert' => 1000,
                'description' => 'High Octane Blending Component (HOBC) premium fuel for sports, turbo, and luxury vehicles.'
            ],
            [
                'name' => 'CNG (Compressed Natural Gas)',
                'code' => 'CNG-01',
                'category' => 'cng_lpg',
                'unit' => 'kg',
                'is_fuel' => true,
                'density_or_octane' => '200 Bar Pressure',
                'tank_or_shelf' => 'Compressor Unit 1',
                'min_stock_alert' => 500,
                'description' => 'Compressed Natural Gas dispensed per Kilogram via high pressure dispenser nozzles.'
            ],
            [
                'name' => 'Auto LPG (Liquid Gas)',
                'code' => 'LPG-01',
                'category' => 'cng_lpg',
                'unit' => 'liter',
                'is_fuel' => true,
                'density_or_octane' => 'Commercial Propane/Butane',
                'tank_or_shelf' => 'LPG Bullet Tank',
                'min_stock_alert' => 500,
                'description' => 'Auto Liquefied Petroleum Gas for converted motor rickshaws, cabs, and private cars.'
            ],
            [
                'name' => 'Caltex Havoline Formula 20W-50 (4L)',
                'code' => 'LUB-HAV-4L',
                'category' => 'lubricant',
                'unit' => 'can',
                'is_fuel' => false,
                'density_or_octane' => '20W-50 API SN/CF',
                'tank_or_shelf' => 'Lube Rack Shelf A',
                'min_stock_alert' => 10,
                'description' => 'Premium mineral passenger car motor oil for extreme temperature protection (4 Liters Can).'
            ],
            [
                'name' => 'Shell Helix HX6 10W-40 (4L)',
                'code' => 'LUB-HLX-4L',
                'category' => 'lubricant',
                'unit' => 'can',
                'is_fuel' => false,
                'density_or_octane' => '10W-40 Synthetic Tech',
                'tank_or_shelf' => 'Lube Rack Shelf A',
                'min_stock_alert' => 8,
                'description' => 'Synthetic technology motor oil for modern petrol and diesel passenger cars.'
            ],
            [
                'name' => 'Diesel Engine Oil 15W-40 (4L Can)',
                'code' => 'LUB-DEO-4L',
                'category' => 'lubricant',
                'unit' => 'can',
                'is_fuel' => false,
                'density_or_octane' => '15W-40 CI-4/SL',
                'tank_or_shelf' => 'Lube Rack Shelf B',
                'min_stock_alert' => 12,
                'description' => 'Heavy duty diesel engine lubricant for commercial vehicles, tractors, and trucks.'
            ],
            [
                'name' => 'Motorcycle 4T Engine Oil 20W-50 (0.7L)',
                'code' => 'LUB-4T-700ML',
                'category' => 'lubricant',
                'unit' => 'bottle',
                'is_fuel' => false,
                'density_or_octane' => '20W-50 JASO MA2',
                'tank_or_shelf' => 'Front Counter Display',
                'min_stock_alert' => 24,
                'description' => 'High performance 4-stroke motorcycle engine oil for 70cc & 125cc motorbikes.'
            ],
            [
                'name' => 'Gear Oil EP 90 / 85W-140 (1L)',
                'code' => 'LUB-GEAR-1L',
                'category' => 'lubricant',
                'unit' => 'bottle',
                'is_fuel' => false,
                'density_or_octane' => 'EP 90 GL-4',
                'tank_or_shelf' => 'Lube Rack Shelf C',
                'min_stock_alert' => 10,
                'description' => 'Extreme pressure automotive gear and transmission lubricant.'
            ],
            [
                'name' => 'Radiator Coolant Green (1L Bottle)',
                'code' => 'CC-COOL-1L',
                'category' => 'car_care',
                'unit' => 'bottle',
                'is_fuel' => false,
                'density_or_octane' => 'Pre-mixed 33%',
                'tank_or_shelf' => 'Car Care Shelf',
                'min_stock_alert' => 15,
                'description' => 'Ready to use anti-rust and anti-freeze engine coolant for car radiators.'
            ],
            [
                'name' => 'Brake Fluid DOT 3 / DOT 4 (250ml)',
                'code' => 'CC-BRK-250ML',
                'category' => 'car_care',
                'unit' => 'bottle',
                'is_fuel' => false,
                'density_or_octane' => 'DOT 3 High Boiling',
                'tank_or_shelf' => 'Front Counter Display',
                'min_stock_alert' => 12,
                'description' => 'Heavy duty hydraulic brake and clutch fluid.'
            ],
            [
                'name' => 'Battery Distilled De-Ionized Water (1L)',
                'code' => 'CC-BAT-1L',
                'category' => 'car_care',
                'unit' => 'bottle',
                'is_fuel' => false,
                'density_or_octane' => 'Pure Distilled',
                'tank_or_shelf' => 'Battery Service Bay',
                'min_stock_alert' => 20,
                'description' => 'Demineralized acid-free distilled water for lead-acid automotive batteries.'
            ],
        ];

        $categories = Product::getAllCategories();
        $units = Product::getAllUnits();

        return view('products.create', compact('suppliers', 'petrolPumpPresets', 'categories', 'units'));
    }

    /**
     * Store a newly created product in storage
     */
    public function store(Request $request)
    {
        // Sanitize comma-separated numeric inputs
        foreach (['purchase_price', 'sale_price', 'opening_stock', 'current_stock', 'min_stock_alert'] as $field) {
            if ($request->has($field) && is_string($request->input($field))) {
                $cleanVal = str_replace(',', '', trim($request->input($field)));
                $request->merge([$field => $cleanVal === '' ? null : $cleanVal]);
            }
        }

        if ($request->has('name')) {
            $request->merge(['name' => trim($request->input('name'))]);
        }
        if ($request->has('code')) {
            $code = trim($request->input('code'));
            $request->merge(['code' => $code === '' ? null : $code]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('products', 'name')->whereNull('deleted_at')],
            'code' => ['nullable', 'string', 'max:100', Rule::unique('products', 'code')->whereNull('deleted_at')],
            'category' => ['required', 'string', 'max:50'],
            'unit' => ['required', 'string', 'max:50', Rule::in(array_keys(Product::getAllUnits()))],
            'is_fuel' => ['nullable', 'boolean'],
            'density_or_octane' => ['nullable', 'string', 'max:100'],
            'tank_or_shelf' => ['nullable', 'string', 'max:100'],
            'supplier_id' => ['nullable', 'exists:parties,id'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'opening_stock' => ['nullable', 'numeric', 'min:0'],
            'min_stock_alert' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.unique' => 'A product with this name already exists in the system.',
            'code.unique' => 'This product code / SKU is already registered.',
        ]);

        $validated['is_fuel'] = $request->boolean('is_fuel') || $validated['category'] === 'fuel';
        $validated['opening_stock'] = $validated['opening_stock'] ?? 0;
        $validated['current_stock'] = $request->filled('current_stock') ? $request->input('current_stock') : $validated['opening_stock'];

        // Auto-generate SKU code if not supplied
        if (empty($validated['code'])) {
            $prefix = match ($validated['category']) {
                'fuel'      => 'FUEL',
                'lubricant' => 'LUB',
                'cng_lpg'   => 'GAS',
                'car_care'  => 'CARE',
                'tuck_shop' => 'MART',
                'service'   => 'SRV',
                default     => 'PRD',
            };
            $validated['code'] = $prefix . '-' . strtoupper(substr(uniqid(), -5));
        }

        $product = Product::create($validated);

        ActivityLog::log(
            action: 'created',
            module: 'products',
            details: "Added petrol pump product '{$product->name}' (Category: {$product->category_label}, Sale Price: Rs. " . number_format($product->sale_price, 2) . ", Initial Stock: " . number_format($product->current_stock, 2) . " {$product->unit_label})"
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Product registered successfully.',
                'product' => $product,
            ], 201);
        }

        return redirect()->route('products.index')->with('success', "Product '{$product->name}' added successfully.");
    }

    /**
     * Display the specified product details
     */
    public function show(Product $product)
    {
        $product->load(['supplier', 'pumps', 'purchases' => fn($q) => $q->latest('purchase_date')->limit(5)]);
        return view('products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified product
     */
    public function edit(Product $product)
    {
        $suppliers = Party::where('type', 'supplier')->orderBy('name')->get(['id', 'name', 'phone']);
        $categories = Product::getAllCategories();
        $units = Product::getAllUnits();
        return view('products.edit', compact('product', 'suppliers', 'categories', 'units'));
    }

    /**
     * Update the specified product in storage
     */
    public function update(Request $request, Product $product)
    {
        // Sanitize numeric inputs
        foreach (['purchase_price', 'sale_price', 'current_stock', 'min_stock_alert'] as $field) {
            if ($request->has($field) && is_string($request->input($field))) {
                $cleanVal = str_replace(',', '', trim($request->input($field)));
                $request->merge([$field => $cleanVal === '' ? null : $cleanVal]);
            }
        }

        if ($request->has('name')) {
            $request->merge(['name' => trim($request->input('name'))]);
        }
        if ($request->has('code')) {
            $code = trim($request->input('code'));
            $request->merge(['code' => $code === '' ? null : $code]);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'name')->ignore($product->id)->whereNull('deleted_at'),
            ],
            'code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'code')->ignore($product->id)->whereNull('deleted_at'),
            ],
            'category' => ['required', 'string', 'max:50'],
            'unit' => ['required', 'string', 'max:50', Rule::in(array_keys(Product::getAllUnits()))],
            'is_fuel' => ['nullable', 'boolean'],
            'density_or_octane' => ['nullable', 'string', 'max:100'],
            'tank_or_shelf' => ['nullable', 'string', 'max:100'],
            'supplier_id' => ['nullable', 'exists:parties,id'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'current_stock' => ['required', 'numeric', 'min:0'],
            'min_stock_alert' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.unique' => 'Another product with this name already exists.',
            'code.unique' => 'This product code / SKU is assigned to another product.',
        ]);

        $validated['is_fuel'] = $request->boolean('is_fuel') || $validated['category'] === 'fuel';

        $oldSalePrice = $product->sale_price;
        $oldCurrentStock = $product->current_stock;

        $product->update($validated);

        // Audit Trail Details
        $logMessage = "Updated product '{$product->name}' details.";
        if ((float) $oldSalePrice != (float) $product->sale_price) {
            $logMessage .= " Rate revision: Rs. " . number_format($oldSalePrice, 2) . " -> Rs. " . number_format($product->sale_price, 2) . ".";
        }
        if ((float) $oldCurrentStock != (float) $product->current_stock) {
            $logMessage .= " Stock adjusted: " . number_format($oldCurrentStock, 2) . " -> " . number_format($product->current_stock, 2) . ".";
        }

        ActivityLog::log(
            action: 'updated',
            module: 'products',
            details: $logMessage
        );

        return redirect()->route('products.index')->with('success', "Product '{$product->name}' updated successfully.");
    }

    /**
     * Remove the specified product from storage (Safe Soft-Delete)
     */
    public function destroy(Product $product)
    {
        $name = $product->name;
        $product->delete();

        ActivityLog::log(
            action: 'deleted',
            module: 'products',
            details: "Removed product '{$name}' (Soft deleted)"
        );

        return redirect()->route('products.index')->with('success', "Product '{$name}' deleted successfully.");
    }

    /**
     * Toggle product active/inactive status
     */
    public function toggleStatus(Product $product)
    {
        $product->status = $product->status === 'active' ? 'inactive' : 'active';
        $product->save();

        ActivityLog::log(
            action: 'updated',
            module: 'products',
            details: "Changed status of product '{$product->name}' to " . strtoupper($product->status)
        );

        $statusLabel = ucfirst($product->status);
        return back()->with('success', "Product status changed to {$statusLabel}.");
    }

    /**
     * Quick price revision (ideal for petrol pumps during fuel rate changes)
     */
    public function quickPriceUpdate(Request $request, Product $product)
    {
        // Sanitize numeric inputs
        foreach (['sale_price', 'purchase_price'] as $field) {
            if ($request->has($field) && is_string($request->input($field))) {
                $cleanVal = str_replace(',', '', trim($request->input($field)));
                $request->merge([$field => $cleanVal === '' ? null : $cleanVal]);
            }
        }

        $validated = $request->validate([
            'sale_price' => ['required', 'numeric', 'min:0'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'price_change_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $oldSale = $product->sale_price;
        $oldPurchase = $product->purchase_price;

        $product->sale_price = $validated['sale_price'];
        if ($request->filled('purchase_price')) {
            $product->purchase_price = $validated['purchase_price'];
        }
        $product->save();

        $reason = $validated['price_change_reason'] ?? 'Official Rate Revision';
        ActivityLog::log(
            action: 'updated',
            module: 'products',
            details: "Fuel / Product Rate Revision for '{$product->name}': Sale Price Rs. " . number_format($oldSale, 2) . " -> Rs. " . number_format($product->sale_price, 2) . " [Reason: {$reason}]"
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Price updated successfully.',
                'new_sale_price' => number_format($product->sale_price, 2),
                'new_purchase_price' => number_format($product->purchase_price, 2),
            ]);
        }

        return back()->with('success', "Price for '{$product->name}' updated to Rs. " . number_format($product->sale_price, 2) . " successfully.");
    }

    /**
     * Live AJAX duplicate check
     */
    public function checkDuplicate(Request $request)
    {
        $name = trim($request->input('name', ''));
        $excludeId = $request->input('exclude_id');

        if (mb_strlen($name) < 1) {
            return response()->json([
                'exact_match' => false,
                'exact_product' => null,
                'suggestions' => [],
            ]);
        }

        $query = Product::query();
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $matches = (clone $query)->where('name', 'like', "%{$name}%")
            ->take(6)
            ->get(['id', 'name', 'code', 'category', 'sale_price', 'current_stock', 'unit']);

        $exactMatch = $matches->first(function ($p) use ($name) {
            return strcasecmp(trim($p->name), $name) === 0;
        });

        return response()->json([
            'exact_match' => (bool) $exactMatch,
            'exact_product' => $exactMatch ? [
                'id' => $exactMatch->id,
                'name' => $exactMatch->name,
                'code' => $exactMatch->code,
                'category' => $exactMatch->category_label,
                'sale_price' => number_format($exactMatch->sale_price, 2),
                'stock' => number_format($exactMatch->current_stock, 2) . ' ' . $exactMatch->unit_label,
            ] : null,
            'suggestions' => $matches->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'code' => $p->code,
                'category' => $p->category_label,
                'sale_price' => number_format($p->sale_price, 2),
                'stock' => number_format($p->current_stock, 2) . ' ' . $p->unit_label,
            ]),
        ]);
    }

    /**
     * Quick-add a new product category via AJAX modal
     */
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $name = trim($validated['name']);
        $slug = !empty($validated['slug']) ? Str::slug($validated['slug'], '_') : Str::slug($name, '_');
        if (empty($slug)) {
            $slug = 'cat_' . time();
        }

        // Ensure slug uniqueness
        $baseSlug = $slug;
        $counter = 1;
        while (ProductCategory::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '_' . $counter++;
        }

        $category = ProductCategory::create([
            'name' => $name,
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'is_default' => false,
        ]);

        ActivityLog::log(
            action: 'created',
            module: 'products',
            details: "Quick added product category: {$category->name} (Slug: {$category->slug})"
        );

        return response()->json([
            'success' => true,
            'message' => "Category '{$category->name}' created successfully.",
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ],
        ], 201);
    }

    /**
     * Quick-add a new product unit of measurement via AJAX modal
     */
    public function storeUnit(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'short_code' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $name = trim($validated['name']);
        $shortCode = !empty($validated['short_code']) ? strtolower(trim($validated['short_code'])) : Str::slug($name, '_');
        if (empty($shortCode)) {
            $shortCode = 'unit_' . time();
        }

        // Ensure short_code uniqueness
        $baseCode = $shortCode;
        $counter = 1;
        while (ProductUnit::where('short_code', $shortCode)->exists()) {
            $shortCode = $baseCode . '_' . $counter++;
        }

        $unit = ProductUnit::create([
            'name' => $name,
            'short_code' => $shortCode,
            'description' => $validated['description'] ?? null,
            'is_default' => false,
        ]);

        ActivityLog::log(
            action: 'created',
            module: 'products',
            details: "Quick added product unit: {$unit->name} (Code: {$unit->short_code})"
        );

        return response()->json([
            'success' => true,
            'message' => "Unit of measurement '{$unit->name}' created successfully.",
            'unit' => [
                'id' => $unit->id,
                'name' => $unit->name,
                'short_code' => $unit->short_code,
            ],
        ], 201);
    }
}
