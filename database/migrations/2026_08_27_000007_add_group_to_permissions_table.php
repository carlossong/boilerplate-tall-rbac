<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('group')->default('')->after('slug');
            $table->index('group');
        });

        foreach (DB::table('permissions')->select('id', 'slug')->cursor() as $row) {
            $parts = explode('.', (string) $row->slug);

            DB::table('permissions')->where('id', $row->id)->update([
                'group' => count($parts) > 1 ? $parts[0] : 'other',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropIndex(['group']);
            $table->dropColumn('group');
        });
    }
};
