<?php

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
        if (!Schema::hasColumn('tasks', 'project_id')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->unsignedBigInteger('project_id')->nullable()->after('id');

                $table->foreign('project_id')->references('id')->on('projects')
                    ->onDelete('cascade')->onUpdate('cascade');

            });
        }
        if (!Schema::hasColumn('tasks', 'display_order')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->integer('display_order')->nullable()->after('created_by');
            });
        }

        if (!Schema::hasColumn('tasks', 'project_status_id')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->unsignedBigInteger('project_status_id')->nullable()->after('display_order');

                $table->foreign('project_status_id')->references('id')->on('projects_statuses')
                    ->onDelete('set null')->onUpdate('cascade');

            });
        }

        $hasTasks = DB::table('tasks')->exists();   // true if ≥1 row
        if ($hasTasks) {
            DB::table('tasks')
                ->update(['project_id' => 1]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('tasks', 'project_id')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropForeign(['project_id']);
                $table->dropColumn('project_id');
            });
        }
        if (Schema::hasColumn('tasks', 'display_order')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropColumn('display_order');
            });
        }
        if (Schema::hasColumn('tasks', 'project_status_id')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropForeign(['project_status_id']);
                $table->dropColumn('project_status_id');
            });
        }
    }
};
