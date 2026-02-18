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
        if(!Schema::hasTable('files')) {
            Schema::create('files', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('folders_id')->nullable();
                $table->text('title');
                $table->text('original_name');
                $table->unsignedBigInteger('size')->nullable();
                $table->string('mime')->nullable();
                $table->longText('path')->nullable();
                $table->longText('source_text')->nullable();
                $table->longText('checksum_sha256')->nullable();
                $table->string('visibility')->default('public');
                $table->unsignedBigInteger('duration')->nullable();
                $table->json('meta_data')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->foreign('folders_id')->references('id')->on('folders')
                    ->onDelete('set null')->onUpdate('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
