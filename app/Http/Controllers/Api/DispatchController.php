<?php

namespace App\Http\Controllers\Api;

use App\Application\Services\DispatchService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DispatchController extends Controller
{
    public function __construct(
        private DispatchService $dispatchService
    ) {}

    public function index(): JsonResponse
    {
        $dispatches = $this->dispatchService->getAllDispatches();

        return response()->json([
            'success' => true,
            'data' => $dispatches,
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $dispatch = $this->dispatchService->getDispatchById($id);

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
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only Admin can create dispatches (deduct stock).',
            ], 403);
        }

        $validated = $request->validate([
            'inventory_id' => 'required|uuid|exists:inventories,id',
            'warehouse_id' => 'required|uuid|exists:warehouses,id',
            'quantity' => 'required|integer|min:1',
            'destination' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Validate stock availability
        $stockValidation = $this->dispatchService->validateStock(
            $validated['inventory_id'],
            $validated['quantity']
        );

        if (!$stockValidation['valid']) {
            return response()->json([
                'success' => false,
                'message' => $stockValidation['error'],
            ], $stockValidation['code']);
        }

        $dispatch = $this->dispatchService->createDispatch(
            $validated,
            $request->user()->id,
            $request->ip()
        );

        return response()->json([
            'success' => true,
            'data' => $dispatch,
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:pending,in_transit,delivered,cancelled',
            'destination' => 'sometimes|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $dispatch = $this->dispatchService->updateDispatch(
            $id,
            $validated,
            $request->user()->id,
            $request->ip()
        );

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

    public function destroy(string $id): JsonResponse
    {
        $deleted = $this->dispatchService->deleteDispatch($id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Dispatch not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Dispatch deleted successfully',
        ]);
    }
}
