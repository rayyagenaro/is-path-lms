<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 24)->default('student')->index();
            $table->string('avatar')->nullable();
            $table->boolean('is_active')->default(true);
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('nim')->unique();
            $table->unsignedSmallInteger('cohort_year');
            $table->string('study_program')->default('Sistem Informasi');
            $table->foreignId('target_job_role_id')->nullable();
            $table->timestamps();
        });

        Schema::create('lecturers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('nidn')->unique();
            $table->string('expertise')->nullable();
            $table->timestamps();
        });

        Schema::create('competencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('competencies')->nullOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category');
            $table->enum('level_type', ['competency_group', 'skill'])->default('skill');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('expected_evidence_count')->default(3);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('competency_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_competency_id')->constrained('competencies')->cascadeOnDelete();
            $table->foreignId('target_competency_id')->constrained('competencies')->cascadeOnDelete();
            $table->string('relation_type')->default('related');
            $table->unique(['source_competency_id', 'target_competency_id', 'relation_type'], 'competency_relation_unique');
        });

        Schema::create('career_paths', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('job_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_path_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description');
            $table->string('icon')->default('briefcase');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->foreign('target_job_role_id')->references('id')->on('job_roles')->nullOnDelete();
        });

        Schema::create('job_competency_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competency_id')->constrained()->restrictOnDelete();
            $table->decimal('weight', 5, 2);
            $table->unsignedTinyInteger('minimum_level')->default(2);
            $table->enum('requirement_type', ['mandatory', 'recommended', 'optional'])->default('recommended');
            $table->unique(['job_role_id', 'competency_id']);
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lecturer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('code')->unique();
            $table->string('title');
            $table->text('description');
            $table->string('category');
            $table->enum('status', ['draft', 'review', 'published', 'archived'])->default('draft');
            $table->unsignedSmallInteger('duration_minutes')->default(0);
            $table->string('level')->default('Pemula');
            $table->json('learning_outcomes')->nullable();
            $table->timestamps();
        });

        Schema::create('course_competencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competency_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('contribution')->default(50);
            $table->unique(['course_id', 'competency_id']);
        });

        Schema::create('course_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedSmallInteger('position')->default(1);
            $table->timestamps();
        });

        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_section_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->enum('type', ['text', 'video', 'document', 'link', 'quiz', 'assignment', 'project', 'case_study'])->default('text');
            $table->text('content')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->default(10);
            $table->unsignedSmallInteger('position')->default(1);
            $table->timestamps();
        });

        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->enum('type', ['quiz', 'exam', 'assignment', 'project', 'practical'])->default('quiz');
            $table->decimal('passing_score', 5, 2)->default(70);
            $table->unsignedTinyInteger('max_attempts')->default(1);
            $table->unsignedSmallInteger('time_limit_minutes')->nullable();
            $table->enum('attempt_strategy', ['best', 'latest', 'average'])->default('best');
            $table->boolean('is_published')->default(false);
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->timestamps();
        });

        Schema::create('assessment_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['single_choice', 'multiple_choice', 'true_false', 'short_answer', 'essay', 'practical', 'project']);
            $table->text('prompt');
            $table->json('options')->nullable();
            $table->json('answer_key')->nullable();
            $table->json('rubric')->nullable();
            $table->decimal('max_score', 7, 2)->default(1);
            $table->boolean('measures_competency')->default(true);
            $table->unsignedSmallInteger('position')->default(1);
            $table->timestamps();
        });

        Schema::create('question_competencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competency_id')->constrained()->restrictOnDelete();
            $table->decimal('weight', 5, 2)->default(100);
            $table->unique(['assessment_question_id', 'competency_id'], 'question_competency_unique');
        });

        Schema::create('assessment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->uuid('idempotency_key')->unique();
            $table->enum('status', ['in_progress', 'submitted', 'needs_grading', 'graded'])->default('in_progress');
            $table->unsignedTinyInteger('attempt_number')->default(1);
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamp('started_at');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();
            $table->unique(['assessment_id', 'student_id', 'attempt_number']);
        });

        Schema::create('assessment_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_question_id')->constrained()->cascadeOnDelete();
            $table->json('answer')->nullable();
            $table->decimal('score', 7, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['assessment_attempt_id', 'assessment_question_id'], 'attempt_answer_unique');
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->unsignedTinyInteger('progress')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unique(['student_id', 'course_id']);
            $table->timestamps();
        });

        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['not_started', 'started', 'completed'])->default('not_started');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'lesson_id']);
        });

        Schema::create('student_competencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competency_id')->constrained()->restrictOnDelete();
            $table->decimal('score', 5, 2)->default(0);
            $table->decimal('confidence_score', 5, 2)->default(0);
            $table->unsignedTinyInteger('proficiency_level')->default(0);
            $table->timestamp('calculated_at')->nullable();
            $table->unique(['student_id', 'competency_id']);
            $table->timestamps();
        });

        Schema::create('competency_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_competency_id')->constrained()->cascadeOnDelete();
            $table->enum('evidence_type', ['project', 'technical_assessment', 'course_performance', 'self_assessment', 'lecturer_assessment']);
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->decimal('score', 5, 2);
            $table->decimal('weight', 5, 2);
            $table->text('notes')->nullable();
            $table->timestamp('earned_at');
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('competency_score_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_competency_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 5, 2);
            $table->decimal('confidence_score', 5, 2);
            $table->timestamp('recorded_at');
        });

        Schema::create('recommendation_rule_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version')->unique();
            $table->json('configuration');
            $table->boolean('is_active')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('career_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rule_version_id')->constrained('recommendation_rule_versions')->restrictOnDelete();
            $table->decimal('match_score', 5, 2);
            $table->string('category');
            $table->json('explanation_snapshot');
            $table->timestamp('generated_at');
            $table->index(['student_id', 'generated_at']);
        });

        Schema::create('learning_paths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('target_job_role_id')->constrained('job_roles')->cascadeOnDelete();
            $table->enum('status', ['active', 'completed', 'superseded'])->default('active');
            $table->timestamp('generated_at');
            $table->timestamps();
        });

        Schema::create('learning_path_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_path_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('competency_id')->constrained()->restrictOnDelete();
            $table->string('severity');
            $table->boolean('is_mandatory')->default(false);
            $table->unsignedSmallInteger('position');
            $table->boolean('is_completed')->default(false);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['auditable_type', 'auditable_id']);
        });

        Schema::create('learning_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type')->index();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_events');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('learning_path_items');
        Schema::dropIfExists('learning_paths');
        Schema::dropIfExists('career_recommendations');
        Schema::dropIfExists('recommendation_rule_versions');
        Schema::dropIfExists('competency_score_histories');
        Schema::dropIfExists('competency_evidences');
        Schema::dropIfExists('student_competencies');
        Schema::dropIfExists('lesson_progress');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('assessment_answers');
        Schema::dropIfExists('assessment_attempts');
        Schema::dropIfExists('question_competencies');
        Schema::dropIfExists('assessment_questions');
        Schema::dropIfExists('assessments');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('course_sections');
        Schema::dropIfExists('course_competencies');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('job_competency_requirements');
        Schema::table('students', fn (Blueprint $table) => $table->dropForeign(['target_job_role_id']));
        Schema::dropIfExists('job_roles');
        Schema::dropIfExists('career_paths');
        Schema::dropIfExists('competency_relations');
        Schema::dropIfExists('competencies');
        Schema::dropIfExists('lecturers');
        Schema::dropIfExists('students');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['role', 'avatar', 'is_active']));
    }
};
