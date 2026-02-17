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
        if (Schema::hasTable('contracts_files')) {
            Schema::drop('contracts_files');
        }

        Schema::create('contracts_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contracts_id')->nullable();
            $table->string('name')->nullable();
            $table->mediumText('file_path')->nullable();
            $table->string('type')->nullable();
            $table->string('version')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_till')->nullable();
            $table->boolean('is_active')->default(1)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->foreign('contracts_id')->references('id')->on('contracts')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('created_by')->references('id')->on('users')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts_files');
    }
};
