<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('career_clusters', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('display_order')->default(1);
            $table->timestamps();
        });

        Schema::rename('job_roles', 'career_roles');
        Schema::rename('job_competency_requirements', 'career_role_competencies');
        Schema::rename('learning_paths', 'career_learning_paths');
        Schema::rename('learning_path_items', 'career_learning_path_items');

        Schema::table('career_roles', function (Blueprint $table) {
            $table->foreignId('career_cluster_id')->nullable()->constrained('career_clusters')->nullOnDelete();
            $table->string('career_level')->default('standard');
            $table->json('responsibilities')->nullable();
            $table->json('tools')->nullable();
        });
        Schema::table('career_role_competencies', function (Blueprint $table) {
            $table->boolean('is_required')->default(false);
        });
        Schema::table('competencies', function (Blueprint $table) {
            $table->string('category_group')->default('Technical')->index();
        });
        Schema::table('course_competencies', function (Blueprint $table) {
            $table->unsignedTinyInteger('competency_gain')->default(2);
        });
        Schema::table('career_learning_path_items', function (Blueprint $table) {
            $table->string('status')->default('pending');
        });
        Schema::table('assessments', function (Blueprint $table) {
            $table->string('assessment_scope')->default('course');
            $table->unsignedBigInteger('scope_reference_id')->nullable();
            $table->string('assessment_purpose')->default('standard');
        });

        Schema::create('course_prerequisites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prerequisite_course_id')->constrained('courses')->cascadeOnDelete();
            $table->boolean('is_required')->default(true);
            $table->unique(['course_id', 'prerequisite_course_id']);
        });

        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedSmallInteger('order');
            $table->string('type')->default('text');
            $table->text('content_ref')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->default(25);
            $table->boolean('is_optional')->default(false);
            $table->timestamps();
            $table->unique(['course_id', 'order']);
        });

        Schema::create('module_competencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competency_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('competency_gain')->default(1);
            $table->unique(['module_id', 'competency_id']);
        });

        Schema::create('module_prerequisites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prerequisite_module_id')->constrained('modules')->cascadeOnDelete();
            $table->unique(['module_id', 'prerequisite_module_id']);
        });

        Schema::create('student_module_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('not_started');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'module_id']);
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->foreignId('related_course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('project_competencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competency_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('required_level')->default(2);
            $table->unique(['project_id', 'competency_id']);
        });

        Schema::create('project_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('submission_ref');
            $table->text('student_note')->nullable();
            $table->string('status')->default('submitted');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('student_career_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('career_role_id')->constrained('career_roles')->cascadeOnDelete();
            $table->string('interest_type')->default('explored');
            $table->timestamps();
            $table->unique(['student_id', 'career_role_id', 'interest_type'], 'student_career_interest_unique');
        });

        Schema::create('career_readiness_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('career_role_id')->constrained('career_roles')->cascadeOnDelete();
            $table->decimal('readiness_score', 5, 2);
            $table->string('status');
            $table->boolean('has_blocking_gap')->default(false);
            $table->json('breakdown_snapshot');
            $table->timestamp('calculated_at');
            $table->timestamps();
            $table->index(['student_id', 'career_role_id', 'calculated_at'], 'career_readiness_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_readiness_scores');
        Schema::dropIfExists('student_career_interests');
        Schema::dropIfExists('project_submissions');
        Schema::dropIfExists('project_competencies');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('student_module_progress');
        Schema::dropIfExists('module_prerequisites');
        Schema::dropIfExists('module_competencies');
        Schema::dropIfExists('modules');
        Schema::dropIfExists('course_prerequisites');
        Schema::table('assessments', fn (Blueprint $table) => $table->dropColumn(['assessment_scope', 'scope_reference_id', 'assessment_purpose']));
        Schema::table('career_learning_path_items', fn (Blueprint $table) => $table->dropColumn('status'));
        Schema::table('course_competencies', fn (Blueprint $table) => $table->dropColumn('competency_gain'));
        Schema::table('competencies', fn (Blueprint $table) => $table->dropColumn('category_group'));
        Schema::table('career_role_competencies', fn (Blueprint $table) => $table->dropColumn('is_required'));
        Schema::table('career_roles', fn (Blueprint $table) => $table->dropConstrainedForeignId('career_cluster_id'));
        Schema::table('career_roles', fn (Blueprint $table) => $table->dropColumn(['career_level', 'responsibilities', 'tools']));
        Schema::rename('career_learning_path_items', 'learning_path_items');
        Schema::rename('career_learning_paths', 'learning_paths');
        Schema::rename('career_role_competencies', 'job_competency_requirements');
        Schema::rename('career_roles', 'job_roles');
        Schema::dropIfExists('career_clusters');
    }
};
