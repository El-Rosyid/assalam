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
        Schema::create('assessment_rating_descriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assessment_variable_id');
            $table->string('rating');
            $table->text('description');
            $table->timestamps();
            
            // Foreign key
            $table->foreign('assessment_variable_id')
                  ->references('id')
                  ->on('assessment_variable')
                  ->onDelete('cascade');
                  
            // Unique constraint - satu assessment_variable hanya bisa punya satu deskripsi per rating
            $table->unique(['assessment_variable_id', 'rating'], 'ard_variable_rating_unique');
            
            // Index untuk performance
            $table->index(['assessment_variable_id', 'rating'], 'ard_variable_rating_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_rating_descriptions');
    }
};
