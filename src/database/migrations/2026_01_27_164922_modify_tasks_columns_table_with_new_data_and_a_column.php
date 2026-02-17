<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\TasksColumns;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insert the Default Column Size column in to the tasks_columns table
        if(!Schema::hasColumn('tasks_columns','default_size')){
            Schema::table('tasks_columns', function (Blueprint $table) {
                $table->string('default_size')->after('is_shown')->default('medium')->nullable();
            });
        }

        // Insert the New Records of Status and Categories
        if (!TasksColumns::where('name', 'status')->exists()) {
            TasksColumns::insert([
                [
                    'name' => 'status',
                    'display_order' => 8,
                    'is_shown' => 1,
                    'default_size' => 'medium',
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ],
                [
                    'name' => 'categories',
                    'display_order' => 9,
                    'is_shown' => 0,
                    'default_size' => 'medium',
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]
            ]);
        }

        // Update the DisplayOrder and Default Size of the Columns
        DB::table('tasks_columns')->update([
            'display_order' => DB::raw("
                CASE id
                    WHEN 1 THEN 3
                    WHEN 2 THEN 1
                    WHEN 3 THEN 5
                    WHEN 4 THEN 4
                    WHEN 5 THEN 6
                    WHEN 6 THEN 8
                    WHEN 7 THEN 9
                    WHEN 8 THEN 2
                    WHEN 9 THEN 7
                    ELSE display_order
                END
            "),

            'is_shown' => DB::raw("
                CASE id
                    WHEN 1 THEN 1
                    WHEN 2 THEN 1
                    WHEN 3 THEN 1
                    WHEN 4 THEN 1
                    WHEN 5 THEN 0
                    WHEN 6 THEN 0
                    WHEN 7 THEN 0
                    WHEN 8 THEN 1
                    WHEN 9 THEN 0
                    ELSE is_shown
                END
            "),

            'default_size' => DB::raw("
                CASE id
                    WHEN 2 THEN 'large'
                    WHEN 6 THEN 'small'
                    ELSE default_size
                END
            "),
        ]);

        // Also add the column in Users Tasks Columns
        if(!Schema::hasColumn('users_tasks_columns','column_size')){
            Schema::table('users_tasks_columns', function (Blueprint $table) {
                $table->string('column_size')->after('is_shown')->default('medium')->nullable();
            });
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if(Schema::hasColumn('tasks_columns','default_size')){
            Schema::table('tasks_columns', function (Blueprint $table) {
                $table->dropColumn('default_size');
            });
        }

        if(Schema::hasColumn('users_tasks_columns','column_size')){
            Schema::table('users_tasks_columns', function (Blueprint $table) {
                $table->dropColumn('column_size');
            });
        }
    }
};
