<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email')->unique();
            $table->string('phone', 40)->nullable();
            $table->string('department', 100);
            $table->date('hire_date')->nullable();
            $table->unsignedInteger('salary')->default(0);
            $table->timestamps();
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_number', 20)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email')->nullable()->unique();
            $table->date('date_of_birth')->nullable();
            $table->unsignedTinyInteger('year_level')->default(1);
            $table->string('guardian_name', 200)->nullable();
            $table->string('guardian_phone', 40)->nullable();
            $table->string('status', 20)->default('active'); // active | inactive | graduated
            $table->timestamps();
            $table->index(['last_name', 'first_name']);
        });

        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('code', 20)->unique();
            $table->string('subject', 100);
            $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();
            $table->string('room', 50)->nullable();
            $table->string('schedule', 100)->nullable();
            $table->string('term', 50);
            $table->unsignedSmallInteger('capacity')->default(30);
            $table->text('description')->nullable();
            $table->json('weights')->nullable(); // per-category weights; null = school defaults
            $table->timestamps();
        });

        Schema::create('classroom_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('enrolled_on')->nullable();
            $table->timestamps();
            $table->unique(['classroom_id', 'student_id']);
        });

        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->string('category', 20);
            $table->decimal('max_points', 7, 2);
            $table->date('due_date')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['classroom_id', 'due_date']);
        });

        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 7, 2)->nullable();
            $table->boolean('excused')->default(false);
            $table->string('comment', 500)->nullable();
            $table->timestamps();
            $table->unique(['assignment_id', 'student_id']);
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('status', 20); // present | late | absent | excused
            $table->timestamps();
            $table->unique(['classroom_id', 'student_id', 'date']);
            $table->index(['classroom_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('grades');
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('classroom_student');
        Schema::dropIfExists('classrooms');
        Schema::dropIfExists('students');
        Schema::dropIfExists('teachers');
    }
};
