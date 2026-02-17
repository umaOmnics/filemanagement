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
        if (!Schema::hasColumn('files', 'object_key')) {
            Schema::table('files', function (Blueprint $table) {
                $table->text('object_key')->nullable()->after('mime');
                $table->boolean('is_entity')->default(0)->after('visibility');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('files', 'object_key')) {
            Schema::table('files', function (Blueprint $table) {
                $table->dropColumn(['object_key', 'is_entity']);
            });
        }
    }
};
