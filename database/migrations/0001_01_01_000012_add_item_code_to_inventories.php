<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->string('item_code')->nullable()->after('id');
        });

        // Generate item codes for existing records
        $inventories = \App\Models\Inventory::whereNull('item_code')->get();
        $dateCode = now()->format('Ymd');
        $sequence = 1;
        
        foreach ($inventories as $inventory) {
            $inventory->update([
                'item_code' => sprintf("ITM-%s-%04d", $dateCode, $sequence++)
            ]);
        }

        // Now make it unique (after populating)
        Schema::table('inventories', function (Blueprint $table) {
            $table->unique('item_code');
        });
    }

    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn('item_code');
        });
    }
};
