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
        if(!Schema::hasTable('tags')){
            Schema::dropIfExists('tags');

            Schema::create('tags', function (Blueprint $table) {
                $table->id();
                $table->string('module')->nullable();
                $table->string('name');
                $table->string('color')->default('#ADD8E6');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
