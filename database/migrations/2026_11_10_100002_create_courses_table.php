<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('title');
            $table->unsignedSmallInteger('credit_units')->default(3);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['department_id', 'code']);
        });

        Schema::create('course_lecturers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lecturer_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['course_id', 'lecturer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_lecturers');
        Schema::dropIfExists('courses');
    }
};
