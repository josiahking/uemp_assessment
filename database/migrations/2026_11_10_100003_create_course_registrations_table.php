<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('academic_year', 9);
            $table->string('semester', 20);
            $table->string('status', 20)->default('pending');
            $table->timestamps();
            $table->unique(['student_id', 'course_id', 'academic_year', 'semester'], 'course_registration_unique');
            $table->index(['course_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_registrations');
    }
};