# IS-Path Implementation State

## 1. Stack

- Framework: Laravel.
- Frontend: Blade templates, custom CSS, small vanilla JavaScript.
- Database: PostgreSQL for app runtime; SQLite exists only as a local artifact/test convenience.
- Ontology source: `Ontologi/LMS FIX.rdf`.
- Styling source of truth: `DESIGN.md`.

## 2. Active Routes

Core web routes:

- `/login`
- `/register`
- `/guest`
- `/dashboard`
- `/career-profile`
- `/careers`
- `/careers/{slug}`
- `/courses`
- `/courses/{slug}`
- `/courses/{slug}/modules/{module}`
- `/assessments`
- `/assessments/{assessment}/start`
- `/assessments/attempts/{attempt}`
- `/assessments/attempts/{attempt}/result`
- `/competencies`
- `/projects`

Core API routes:

- `/api/health`
- `/api/v1/career-clusters`
- `/api/v1/career-roles/{slug}`
- `/api/v1/career-roles/{slug}/readiness`
- `/api/v1/competencies/me`
- `/api/v1/courses`
- `/api/v1/courses/{course}/modules`
- `/api/v1/ontology/graph`
- `/api/v1/recommendations/me`

## 3. Main Controllers

- `AuthController`: login, logout, student registration.
- `DashboardController`: dashboard, courses, modules, career profile, competencies.
- `CareerController`: career listing, detail, target career selection.
- `AssessmentController`: assessment list, start, take, submit, result.
- `ProjectController`: portfolio project listing and submission.
- API controllers under `app/Http/Controllers/Api`.

## 4. Domain Services

- `CareerIntelligenceService`: role ranking, exploration ordering, role detail, comparison, readiness snapshot.
- `RecommendationEngine`: weighted role matching and mandatory gap behavior.
- `RecommendationWorkflowService`: post-assessment recommendation refresh, recommendation persistence, ontology trace, learning path regeneration.
- `StudentScoreProfile`: combines verified score and self-rating.
- `CompetencyScoreCalculator`: updates student competency from evidence.
- `AssessmentService`: starts and grades assessment attempts.
- `LearningPathService`: regenerates active career learning paths.
- `OntologyRepository`: reads RDF/XML metadata and rules.
- `SwrlReasoningService`: maps RDF/SWRL rule semantics to app runtime facts.

## 5. Database Shape

Important tables include:

- `users`
- `students`
- `competencies`
- `competency_relations`
- `student_competencies`
- `competency_evidences`
- `competency_score_histories`
- `career_profiles`
- `career_profile_strengths`
- `career_clusters`
- `career_paths`
- `career_roles`
- `career_role_competencies`
- `student_career_interests`
- `career_readiness_scores`
- `career_recommendations`
- `recommendation_rule_versions`
- `courses`
- `course_sections`
- `lessons`
- `modules`
- `module_competencies`
- `module_prerequisites`
- `course_prerequisites`
- `course_competencies`
- `enrollments`
- `student_module_progress`
- `career_learning_paths`
- `career_learning_path_items`
- `assessments`
- `assessment_questions`
- `assessment_options`
- `assessment_attempts`
- `assessment_answers`
- `projects`
- `project_competencies`
- `project_submissions`
- `learning_events`
- `audit_logs`

## 6. Implemented Product Decisions

- One active role: student.
- Guest mode is read-only preview.
- Career recommendations are locked until post-assessment.
- Career exploration uses pagination, 8 roles per page.
- Self-rating is not reduced to 20% unless verified evidence exists.
- RDF is read-only and not copied into tables.
- SWRL is represented by an application bridge, not a generic SWRL reasoner.
- Existing Laravel/Blade structure is preserved.

## 7. Current Test State

Latest known full test result:

- `php artisan test`
- 22 tests passed.
- 126 assertions.

Important coverage:

- Auth/register.
- Student-only access.
- Career exploration render and pagination.
- Profile update without premature final recommendation.
- Course module open/complete.
- Master data counts.
- Target career after graded assessment.
- Project submission.
- API gate behavior.
- Ontology graph availability.
- Assessment flow and recommendation generation.

## 8. Known Implementation Limits

- Assessment content is not complete for all 40 competencies.
- Project submission is stored, but deeper project validation into competency evidence is still a roadmap item.
- RDF/SWRL bridge validates known rule names and maps selected rules; it does not execute arbitrary SWRL expressions.
- Admin/instructor interfaces from older PRDs are not part of the current product direction.
- Some source documents may mention multi-role features; future agents must treat those as old context unless the user explicitly reactivates them.

