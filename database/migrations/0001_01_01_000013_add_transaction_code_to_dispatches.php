<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatches', function (Blueprint $table) {
            $table->string('transaction_code')->nullable()->after('id');
        });

        // Generate transaction codes for existing records
        $dispatches = \App\Models\Dispatch::whereNull('transaction_code')->get();
        $dateCode = now()->format('Ymd');
        $sequence = 1;
        
        foreach ($dispatches as $dispatch) {
            $dispatch->update([
                'transaction_code' => sprintf("DSP-%s-%04d", $dateCode, $sequence++)
            ]);
        }

        // Now make it unique (after populating)
        Schema::table('dispatches', function (Blueprint $table) {
            $table->unique('transaction_code');
        });
    }

    public function down(): void
    {
        Schema::table('dispatches', function (Blueprint $table) {
            $table->dropColumn('transaction_code');
        });
    }
};
