<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('avatar_path')->nullable()->comment('Path to student avatar in S3');
            $table->string('name', 255)->comment('Student full name');
            $table->unsignedTinyInteger('age')->comment('Student age');
            $table->string('address', 255)->comment('Student address');
            $table->string('email', 100)->unique()->comment('Student email address');
            $table->string('course', 255)->comment('Student course/program');
            
            // Add indexes for frequently searched/filtered columns
            $table->index('name');
            $table->index('email');
            $table->index('course');
            
            // Add soft deletes if you want to keep student records
            $table->softDeletes();
            
            // Created_at and Updated_at timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};