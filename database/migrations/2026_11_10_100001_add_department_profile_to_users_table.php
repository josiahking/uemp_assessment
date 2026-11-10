<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('student_number')->nullable()->unique();
            $table->string('faculty')->nullable();
            $table->string('program')->nullable();
            $table->string('academic_level')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropUnique(['student_number']);
            $table->dropColumn(['student_number', 'faculty', 'program', 'academic_level']);
        });
    }
};
