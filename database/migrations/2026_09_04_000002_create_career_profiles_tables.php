<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('career_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('primary_interest')->nullable();
            $table->enum('work_style', ['analytical', 'collaborative', 'creative', 'structured'])->nullable();
            $table->text('career_goal')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('career_profile_strengths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competency_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('self_rating');
            $table->timestamps();
            $table->unique(['career_profile_id', 'competency_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_profile_strengths');
        Schema::dropIfExists('career_profiles');
    }
};
