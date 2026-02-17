<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('tasks', 'created_contact_by')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->json('created_contact_by')->nullable()->after('created_by');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('tasks', 'created_contact_by')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropColumn('created_contact_by');
            });
        }
    }
};
