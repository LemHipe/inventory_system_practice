<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $totalItems = Inventory::query()->count();
        $totalWarehouses = Warehouse::query()->count();
        $lowStockCount = Inventory::query()->where('quantity', '<=', 20)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_items' => $totalItems,
                'total_warehouses' => $totalWarehouses,
                'low_stock_count' => $lowStockCount,
            ],
        ]);
    }
}
