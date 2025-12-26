<?php

namespace App\Http\Controllers\Api;

use App\Application\Services\WarehouseService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function __construct(
        private WarehouseService $warehouseService
    ) {}

    public function index(): JsonResponse
    {
        $warehouses = $this->warehouseService->getAllWarehouses();

        return response()->json([
            'success' => true,
            'data' => array_map(fn($w) => $w->toArray(), $warehouses),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $warehouse = $this->warehouseService->getWarehouseById($id);

        if (!$warehouse) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $warehouse->toArray(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
        ]);

        $warehouse = $this->warehouseService->createWarehouse($validated);

        return response()->json([
            'success' => true,
            'data' => $warehouse->toArray(),
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
        ]);

        $warehouse = $this->warehouseService->updateWarehouse($id, $validated);

        if (!$warehouse) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $warehouse->toArray(),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $deleted = $this->warehouseService->deleteWarehouse($id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Warehouse deleted successfully',
        ]);
    }
}
