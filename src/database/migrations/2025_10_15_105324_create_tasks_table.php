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
            Schema::create('tasks', function (Blueprint $table) {
                $table->id();
                $table->integer('parent_id')->nullable();
                $table->string('title')->nullable();
                $table->longText('description')->nullable();
                $table->string('priority')->nullable();
                $table->timestamp('due_date')->nullable();
                $table->string('number')->nullable();
                $table->string('final_task_number')->nullable();
                $table->string('type')->nullable();
                $table->json('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_general_ci';
            });
//        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
