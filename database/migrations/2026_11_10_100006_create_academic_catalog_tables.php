<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faculties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('academic_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['department_id', 'name']);
        });

        Schema::create('academic_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 30);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['department_id', 'code']);
        });

        Schema::create('academic_program_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_level_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['academic_program_id', 'academic_level_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_program_levels');
        Schema::dropIfExists('academic_programs');
        Schema::dropIfExists('academic_levels');
        Schema::dropIfExists('faculties');
    }
};
