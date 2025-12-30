<?php

namespace App\Imports;

use App\Models\Inventory;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class InventoryImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    protected array $created = [];
    protected array $errors = [];
    protected array $skipped = [];
    protected array $warehouseCache = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because index is 0-based and header is row 1

            $productName = trim($row['product_name'] ?? '');
            $warehouseName = trim($row['warehouse'] ?? '');
            $unit = trim($row['unit'] ?? '');

            // Warehouse is required
            if (empty($warehouseName)) {
                $this->skipped[] = [
                    'row' => $rowNumber,
                    'product_name' => $productName,
                    'category' => trim($row['category'] ?? ''),
                    'quantity' => $row['quantity'] ?? '',
                    'price' => $row['price'] ?? '',
                    'item_code' => trim($row['item_code'] ?? ''),
                    'description' => trim($row['description'] ?? ''),
                    'warehouse' => $warehouseName,
                    'reason' => "Warehouse is required",
                ];
                continue;
            }

            // Use savepoint for each row to handle PostgreSQL transaction requirements
            $savepointName = "row_{$index}";
            
            try {
                DB::statement("SAVEPOINT {$savepointName}");

                // Resolve warehouse_id from warehouse name (with caching) - auto-creates if not exists
                $warehouseId = $this->resolveWarehouseId($warehouseName);

                // Check if product_name + warehouse_id combination already exists
                if (Inventory::where('product_name', $productName)->where('warehouse_id', $warehouseId)->exists()) {
                    DB::statement("ROLLBACK TO SAVEPOINT {$savepointName}");
                    $this->skipped[] = [
                        'row' => $rowNumber,
                        'product_name' => $productName,
                        'category' => trim($row['category'] ?? ''),
                        'quantity' => $row['quantity'] ?? '',
                        'price' => $row['price'] ?? '',
                        'item_code' => trim($row['item_code'] ?? ''),
                        'description' => trim($row['description'] ?? ''),
                        'warehouse' => $warehouseName,
                        'reason' => "Product '{$productName}' already exists in warehouse '{$warehouseName}'",
                    ];
                    continue;
                }

                // Generate or use provided item_code
                $itemCode = trim($row['item_code'] ?? '');
                if (empty($itemCode)) {
                    $itemCode = $this->generateItemCode();
                } else {
                    // Check if item_code already exists
                    if (Inventory::where('item_code', $itemCode)->exists()) {
                        DB::statement("ROLLBACK TO SAVEPOINT {$savepointName}");
                        $this->skipped[] = [
                            'row' => $rowNumber,
                            'product_name' => $productName,
                            'category' => trim($row['category'] ?? ''),
                            'quantity' => $row['quantity'] ?? '',
                            'price' => $row['price'] ?? '',
                            'item_code' => $itemCode,
                            'description' => trim($row['description'] ?? ''),
                            'warehouse' => $warehouseName,
                            'reason' => "Item code '{$itemCode}' already exists",
                        ];
                        continue;
                    }
                }

                $inventory = Inventory::create([
                    'item_code' => $itemCode,
                    'product_id' => Str::uuid()->toString(),
                    'product_name' => $productName,
                    'description' => trim($row['description'] ?? ''),
                    'quantity' => (int) $row['quantity'],
                    'price' => (float) $row['price'],
                    'category' => trim($row['category']),
                    'unit' => $unit !== '' ? $unit : 'pcs',
                    'warehouse_id' => $warehouseId,
                ]);

                DB::statement("RELEASE SAVEPOINT {$savepointName}");
                $this->created[] = $inventory;
            } catch (\Exception $e) {
                DB::statement("ROLLBACK TO SAVEPOINT {$savepointName}");
                $this->errors[] = "Row {$rowNumber}: " . $e->getMessage();
            }
        }
    }

    protected function generateItemCode(): string
    {
        $dateCode = now()->format('Ymd');
        $lastItem = Inventory::where('item_code', 'like', "ITM-{$dateCode}-%")
            ->orderBy('item_code', 'desc')
            ->first();

        $sequence = 1;
        if ($lastItem) {
            $lastSequence = (int) substr($lastItem->item_code, -4);
            $sequence = $lastSequence + 1;
        }

        return sprintf("ITM-%s-%04d", $dateCode, $sequence);
    }

    protected function resolveWarehouseId(string $warehouseName): ?string
    {
        $normalizedName = strtolower($warehouseName);
        
        if (isset($this->warehouseCache[$normalizedName])) {
            return $this->warehouseCache[$normalizedName];
        }

        $warehouse = Warehouse::whereRaw('LOWER(name) = ?', [$normalizedName])->first();
        
        if (!$warehouse) {
            // Auto-create warehouse if it doesn't exist
            $warehouse = Warehouse::create([
                'name' => $warehouseName,
                'address' => 'To be updated',
                'is_active' => true,
            ]);
        }
        
        $this->warehouseCache[$normalizedName] = $warehouse->id;
        return $warehouse->id;
    }

    public function rules(): array
    {
        return [
            'product_name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
            'item_code' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'warehouse' => ['required', 'string', 'max:255'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'product_name.required' => 'Product name is required',
            'category.required' => 'Category is required',
            'quantity.required' => 'Quantity is required',
            'quantity.numeric' => 'Quantity must be a number',
            'price.required' => 'Price is required',
            'price.numeric' => 'Price must be a number',
            'unit.max' => 'Unit must be 50 characters or less',
            'warehouse.required' => 'Warehouse is required',
        ];
    }

    public function getCreated(): array
    {
        return $this->created;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getSkipped(): array
    {
        return $this->skipped;
    }
}
