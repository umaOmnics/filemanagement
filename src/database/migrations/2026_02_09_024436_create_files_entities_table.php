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
        if(!Schema::hasTable('files_entities')){
            Schema::dropIfExists('files_entities');

            Schema::create('files_entities', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('files_id');
                $table->string('entity_type');
                $table->unsignedBigInteger('entity_id');
                $table->timestamps();

                $table->foreign('files_id')->references('id')->on('files')
                    ->onDelete('cascade')->onUpdate('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files_entities');
    }
};
