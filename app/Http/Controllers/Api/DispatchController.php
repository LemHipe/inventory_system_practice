<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Dispatch;
use App\Models\Inventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DispatchController extends Controller
{
    public function index(): JsonResponse
    {
        $dispatches = Dispatch::with(['inventory', 'warehouse', 'dispatcher'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $dispatches,
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $dispatch = Dispatch::with(['inventory', 'warehouse', 'dispatcher'])->find($id);

        if (!$dispatch) {
            return response()->json([
                'success' => false,
                'message' => 'Dispatch not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $dispatch,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'inventory_id' => 'required|uuid|exists:inventories,id',
            'warehouse_id' => 'required|uuid|exists:warehouses,id',
            'quantity' => 'required|integer|min:1',
            'destination' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Check if inventory has enough stock
        $inventory = Inventory::find($validated['inventory_id']);
        
        if (!$inventory) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory item not found',
            ], 404);
        }

        if ($inventory->quantity < $validated['quantity']) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock. Available: ' . $inventory->quantity,
            ], 422);
        }

        $oldQuantity = $inventory->quantity;

        // Generate transaction code: DSP-YYYYMMDD-XXXX
        $dateCode = now()->format('Ymd');
        $lastDispatch = Dispatch::where('transaction_code', 'like', "DSP-{$dateCode}-%")
            ->orderBy('transaction_code', 'desc')
            ->first();
        
        $sequence = 1;
        if ($lastDispatch) {
            $lastSequence = (int) substr($lastDispatch->transaction_code, -4);
            $sequence = $lastSequence + 1;
        }
        $transactionCode = sprintf("DSP-%s-%04d", $dateCode, $sequence);

        // Use transaction to ensure both operations succeed or fail together
        $dispatch = DB::transaction(function () use ($validated, $request, $inventory, $transactionCode) {
            // Deduct inventory stock
            $inventory->decrement('quantity', $validated['quantity']);

            // Create dispatch record
            return Dispatch::create([
                'transaction_code' => $transactionCode,
                'inventory_id' => $validated['inventory_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'dispatcher_id' => $request->user()->id,
                'quantity' => $validated['quantity'],
                'destination' => $validated['destination'] ?? 'Bosun Hardware',
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending',
                'dispatched_at' => now(),
            ]);
        });

        // Log the dispatch activity
        ActivityLog::log(
            userId: $request->user()->id,
            action: 'dispatched',
            modelType: 'Dispatch',
            modelId: $dispatch->id,
            description: "Dispatched {$validated['quantity']} units of {$inventory->product_name} to {$dispatch->destination} (Stock: {$oldQuantity} → {$inventory->fresh()->quantity})",
            newValues: $dispatch->toArray(),
            ipAddress: $request->ip()
        );

        return response()->json([
            'success' => true,
            'data' => $dispatch->load(['inventory', 'warehouse', 'dispatcher']),
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $dispatch = Dispatch::find($id);

        if (!$dispatch) {
            return response()->json([
                'success' => false,
                'message' => 'Dispatch not found',
            ], 404);
        }

        $validated = $request->validate([
            'status' => 'sometimes|in:pending,in_transit,delivered,cancelled',
            'destination' => 'sometimes|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Set delivered_at when status changes to delivered
        if (isset($validated['status']) && $validated['status'] === 'delivered') {
            $validated['delivered_at'] = now();
        }

        $oldValues = $dispatch->toArray();
        $oldStatus = $dispatch->status;
        $dispatch->update($validated);

        // Log status changes
        if (isset($validated['status']) && $oldStatus !== $validated['status']) {
            $inventory = $dispatch->inventory;
            ActivityLog::log(
                userId: $request->user()->id,
                action: 'status_changed',
                modelType: 'Dispatch',
                modelId: $dispatch->id,
                description: "Dispatch status changed: {$oldStatus} → {$validated['status']} ({$inventory->product_name} to {$dispatch->destination})",
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => $validated['status']],
                ipAddress: $request->ip()
            );
        }

        return response()->json([
            'success' => true,
            'data' => $dispatch->fresh()->load(['inventory', 'warehouse', 'dispatcher']),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $dispatch = Dispatch::find($id);

        if (!$dispatch) {
            return response()->json([
                'success' => false,
                'message' => 'Dispatch not found',
            ], 404);
        }

        $dispatch->delete();

        return response()->json([
            'success' => true,
            'message' => 'Dispatch deleted successfully',
        ]);
    }
}
