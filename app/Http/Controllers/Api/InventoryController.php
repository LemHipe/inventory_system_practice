<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\InventoryImport;
use App\Models\ActivityLog;
use App\Models\Inventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class InventoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Inventory::with('warehouse');
        
        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'ilike', "%{$search}%")
                  ->orWhere('item_code', 'ilike', "%{$search}%")
                  ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        // Get unique categories for filter
        $categories = Inventory::distinct()->pluck('category')->filter()->values();

        $items = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $items,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'quantity' => ['required', 'integer', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'category' => ['required', 'string', 'max:255'],
            'warehouse_id' => ['nullable', 'uuid', 'exists:warehouses,id'],
        ]);

        // Generate item code: ITM-YYYYMMDD-XXXX
        $dateCode = now()->format('Ymd');
        $lastItem = Inventory::where('item_code', 'like', "ITM-{$dateCode}-%")
            ->orderBy('item_code', 'desc')
            ->first();
        
        $sequence = 1;
        if ($lastItem) {
            $lastSequence = (int) substr($lastItem->item_code, -4);
            $sequence = $lastSequence + 1;
        }
        $itemCode = sprintf("ITM-%s-%04d", $dateCode, $sequence);

        $inventory = Inventory::create([
            'item_code' => $itemCode,
            'product_id' => Str::uuid()->toString(),
            'product_name' => $validated['product_name'],
            'description' => $validated['description'] ?? '',
            'quantity' => (int) $validated['quantity'],
            'price' => (float) $validated['price'],
            'category' => $validated['category'],
            'warehouse_id' => $validated['warehouse_id'] ?? null,
        ]);

        // Log the activity
        ActivityLog::log(
            userId: $request->user()->id,
            action: 'created',
            modelType: 'Inventory',
            modelId: $inventory->id,
            description: "Added new inventory item: {$inventory->product_name} (Qty: {$inventory->quantity})",
            newValues: $inventory->toArray(),
            ipAddress: $request->ip()
        );

        return response()->json([
            'success' => true,
            'data' => $inventory,
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $item = Inventory::with('warehouse')->find($id);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $item,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $item = Inventory::find($id);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $validated = $request->validate([
            'product_name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'quantity' => ['sometimes', 'integer', 'min:0'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'category' => ['sometimes', 'string', 'max:255'],
            'warehouse_id' => ['nullable', 'uuid', 'exists:warehouses,id'],
        ]);

        // State management: User role can only ADD (increase quantity), not deduct
        if ($request->user()->role !== 'admin' && isset($validated['quantity'])) {
            $currentQuantity = $item->quantity;
            $newQuantity = $validated['quantity'];
            
            if ($newQuantity < $currentQuantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Users can only add stock (increase quantity). Only Admin can deduct stock.',
                    'current_quantity' => $currentQuantity,
                    'attempted_quantity' => $newQuantity,
                ], 403);
            }
        }

        $oldValues = $item->toArray();
        $item->update($validated);

        $freshItem = $item->fresh();
        $newValues = $freshItem->toArray();

        $description = "Updated inventory item: {$freshItem->product_name}";
        if (array_key_exists('quantity', $validated)) {
            $oldQuantity = $oldValues['quantity'] ?? null;
            $newQuantity = $newValues['quantity'] ?? null;
            if ($oldQuantity !== null && $newQuantity !== null && $oldQuantity !== $newQuantity) {
                $description .= " (Qty: {$oldQuantity} → {$newQuantity})";
            }
        }

        // Log the activity
        ActivityLog::log(
            userId: $request->user()->id,
            action: 'updated',
            modelType: 'Inventory',
            modelId: $item->id,
            description: $description,
            oldValues: $oldValues,
            newValues: $newValues,
            ipAddress: $request->ip()
        );

        return response()->json([
            'success' => true,
            'data' => $freshItem,
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        $item = Inventory::find($id);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $itemData = $item->toArray();

        // Log the activity
        ActivityLog::log(
            userId: $request->user()->id,
            action: 'deleted',
            modelType: 'Inventory',
            modelId: $item->id,
            description: "Deleted inventory item: {$item->product_name}",
            oldValues: $itemData,
            ipAddress: $request->ip()
        );

        $item->delete();

        return response()->json(['success' => true]);
    }

    public function addStock(Request $request, string $id): JsonResponse
    {
        $item = Inventory::find($id);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
        ]);

        $oldQuantity = $item->quantity;
        $item->increment('quantity', $validated['amount']);

        // Log the activity
        ActivityLog::log(
            userId: $request->user()->id,
            action: 'stock_added',
            modelType: 'Inventory',
            modelId: $item->id,
            description: "Added stock to {$item->product_name}: +{$validated['amount']} (was {$oldQuantity}, now {$item->fresh()->quantity})",
            oldValues: ['quantity' => $oldQuantity],
            newValues: ['quantity' => $item->fresh()->quantity],
            ipAddress: $request->ip()
        );

        return response()->json([
            'success' => true,
            'data' => $item->fresh(),
        ]);
    }

    public function removeStock(Request $request, string $id): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only Admin can deduct stock.',
            ], 403);
        }

        $item = Inventory::find($id);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
        ]);

        if ($item->quantity < $validated['amount']) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock',
            ], 422);
        }

        $oldQuantity = $item->quantity;
        $item->decrement('quantity', $validated['amount']);

        // Log the activity
        ActivityLog::log(
            userId: $request->user()->id,
            action: 'stock_removed',
            modelType: 'Inventory',
            modelId: $item->id,
            description: "Removed stock from {$item->product_name}: -{$validated['amount']} (was {$oldQuantity}, now {$item->fresh()->quantity})",
            oldValues: ['quantity' => $oldQuantity],
            newValues: ['quantity' => $item->fresh()->quantity],
            ipAddress: $request->ip()
        );

        return response()->json([
            'success' => true,
            'data' => $item->fresh(),
        ]);
    }

    public function uploadCsv(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:5120'],
        ]);

        try {
            $import = new InventoryImport();
            Excel::import($import, $request->file('file'));

            $created = $import->getCreated();
            $errors = $import->getErrors();
            $skipped = $import->getSkipped();

            // Log the bulk upload activity
            if (count($created) > 0) {
                ActivityLog::log(
                    userId: $request->user()->id,
                    action: 'bulk_created',
                    modelType: 'Inventory',
                    modelId: null,
                    description: "Bulk uploaded " . count($created) . " inventory items via CSV/Excel" . (count($skipped) > 0 ? " (" . count($skipped) . " duplicates skipped)" : ""),
                    newValues: ['count' => count($created), 'items' => array_map(fn($i) => $i->item_code, $created)],
                    ipAddress: $request->ip()
                );
            }

            $message = count($created) . ' items imported successfully';
            if (count($skipped) > 0) {
                $message .= '. ' . count($skipped) . ' duplicate(s) skipped';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'created_count' => count($created),
                'skipped_count' => count($skipped),
                'error_count' => count($errors),
                'skipped' => $skipped,
                'errors' => $errors,
                'data' => $created,
            ]);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errors = [];
            foreach ($failures as $failure) {
                $errors[] = "Row {$failure->row()}: {$failure->attribute()} - " . implode(', ', $failure->errors());
            }
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors,
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import file: ' . $e->getMessage(),
            ], 400);
        }
    }
}
