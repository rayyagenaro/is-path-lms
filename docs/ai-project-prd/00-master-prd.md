# IS-Path Consolidated Product Requirements Document

## 1. Product Summary

IS-Path is a career-oriented competency-based learning management system for Information Systems students. The application helps a student understand their interests, explore career roles, complete structured learning modules, take assessments, build verified competency evidence, receive explainable career recommendations, and follow a personalized learning path.

The current product direction is student-only. There is no active lecturer, admin, or multi-user workflow in the application UI. Earlier PRD concepts for lecturers, admins, and committees are preserved as future roadmap context, not current implementation scope.

## 2. Product Vision

IS-Path should feel like a thoughtful academic career advisor embedded inside an LMS. It should not simply list courses. It should connect:

- who the student is,
- what the student wants,
- what competencies they have,
- what career roles require,
- what gaps remain,
- what course/module/project should be done next,
- and why the recommendation appears.

## 3. Primary User

The primary and only active role is `Mahasiswa`.

The student wants to:

- register and enter the app,
- describe career interest, work style, goal, and self-rated strengths,
- explore possible IS career roles before a final recommendation is available,
- learn through course modules,
- complete post-assessment,
- receive career recommendation with match score, readiness, competency gap, and learning path,
- submit project evidence for competency proof.

## 4. Current Core Flow

The intended flow is:

1. Register or login.
2. Complete initial career profile: interest, work style, career goal, self-rating.
3. Explore careers in non-final exploration mode.
4. Browse and take courses/modules related to interests or gaps.
5. Complete post-assessment.
6. Assessment creates competency evidence.
7. Student competency score is recalculated.
8. Recommendation workflow ranks career roles.
9. Ontology/SWRL application bridge evaluates domain rules.
10. Top career recommendations are stored with explanation snapshots.
11. Career readiness and competency gaps are shown.
12. Personalized learning path is regenerated for the active target career.

## 5. Stage Gating Rules

Before post-assessment:

- Career exploration is allowed.
- Role detail pages are allowed.
- Profile self-rating is stored and preserved.
- Final career recommendation is locked.
- Readiness score is not shown as a measured value.
- Target career selection is blocked.
- Personalized learning path is not presented as final recommendation.

After post-assessment:

- Match score and readiness can be shown.
- Career recommendations can be generated and stored.
- Ontology trace can be attached to recommendations.
- Competency gaps can be explained.
- Target career can be selected.
- Learning path can be generated or regenerated.

This gating prevents self-rating from being mistaken as verified skill and prevents zero readiness from being interpreted as an assessment result.

## 6. Feature Requirements

### 6.1 Authentication and Student Account

- Student can register with name, email, NIM, cohort year, study program, password.
- Registration creates `users` and `students` records.
- Login authenticates only active student users for the app.
- Guest preview is read-only and cannot access student data or mutate state.

### 6.2 Career Profile

- Student records primary interest, work style, career goal, and self-rated strengths.
- Self-rating contributes to scoring only as profile input.
- If no verified evidence exists, self-rating is preserved as full initial score for exploration context.
- If verified evidence exists, final score composition uses 80% verified evidence and 20% self-rating.

### 6.3 Career Exploration

- Student sees career roles from 8 clusters.
- Before post-assessment, ordering follows primary interest and cluster order.
- After post-assessment, roles are ranked by recommendation score.
- Listing uses pagination, 8 roles per page.
- Career comparison supports two selected roles.
- Career cards show role name, cluster, level, related skills, and detail link.
- Match/readiness numbers are hidden before post-assessment.

### 6.4 Career Role Detail

- Shows role responsibilities, tools, competency requirements, readiness, and relevant courses.
- Before post-assessment, detail stays exploratory and avoids final personalized claims.
- After post-assessment, it can show measured readiness, gaps, and learning path.

### 6.5 Course and Module Learning

- Course catalog contains 26 centralized reusable courses.
- Course detail shows modules and prerequisite-aware learning.
- Module route supports opening modules and marking them complete.
- Completing modules updates student module progress and enrollment progress.
- Course/module learning is part of competency development, not isolated LMS content.

### 6.6 Assessment

- Assessment list shows available assessment.
- Student can start an assessment attempt.
- Student answers objective questions.
- Submitting complete assessment grades attempt.
- Graded attempt creates competency evidence.
- Competency calculator updates `student_competencies`.
- Assessment completion triggers recommendation refresh.

Current limitation: the implemented post-assessment question bank covers the core Data & Analytics competencies: SQL, Excel, Statistics, Python, Power BI, and Business Understanding. The system architecture supports all 40 competencies, but additional question banks still need to be authored.

### 6.7 Competency Profile

- Competencies are grouped as Technical, Business, and Professional.
- Student competency score stores score, confidence, proficiency level, and calculation timestamp.
- Evidence can come from assessment, module/course progress, and project submission.
- Competency histories support progress tracking.

### 6.8 Recommendation Engine

- Recommendation is rule-based, not machine learning.
- Career role requirements define competency, weight, minimum level, and mandatory/recommended type.
- Match score uses weighted competency matching.
- Mandatory gaps can lower recommendation category even when numeric score is high.
- Top 5 recommendations are stored after post-assessment.
- Explanation snapshot includes rank, basis, strong skills, gaps, blocking gaps, readiness, and ontology trace.

### 6.9 Career Readiness

- Readiness is level-based.
- It compares student proficiency level against minimum required level per role competency.
- Mandatory delta of 2 or more is a blocking gap.
- Readiness bands: Exploring, Beginner, Developing, Career Ready, Highly Ready.
- Blocking mandatory gap can cap high readiness into Developing.

### 6.10 Learning Path

- Active learning path is tied to student and target career role.
- Regeneration supersedes old active paths.
- Items are ranked by severity, mandatory status, and course availability.
- Items connect course, competency, severity, status, and completion state.
- Cross-career skill reuse is supported conceptually by marking already fulfilled competencies.

### 6.11 Project Portfolio

- Published projects can be submitted by students.
- Project submission stores reference URL/note/status.
- Project competencies can act as proof of competency in future validation workflows.

### 6.12 API

Representative API endpoints:

- `GET /api/health`
- `GET /api/v1/career-clusters`
- `GET /api/v1/career-roles/{slug}`
- `GET /api/v1/career-roles/{slug}/readiness`
- `GET /api/v1/competencies/me`
- `GET /api/v1/courses`
- `GET /api/v1/courses/{course}/modules`
- `GET /api/v1/ontology/graph`
- `GET /api/v1/recommendations/me`

Readiness and recommendation endpoints return a locked state before post-assessment.

## 7. Master Data Requirements

The implemented seed target is:

- 1 student demo account.
- 8 career clusters.
- 22 career roles.
- 40 competencies.
- 26 courses.
- 232 modules.
- Career role competency requirements with weights and minimum levels.
- Course competency mappings.
- Course and module prerequisites.
- Assessment bank for initial implemented competency set.

## 8. Design Requirements

The visual direction is defined in `../../DESIGN.md`:

- Calm editorial career-tech.
- White/off-white canvas with forest and emerald accents.
- Poppins typography.
- Minimal, smooth, natural UI.
- No generic AI-slop patterns.
- No purple-blue gradients.
- No card soup or inconsistent button sizing.
- ASCII animation is allowed only on login, not dashboards or career lists.

## 9. Non-Functional Requirements

- Laravel application with Blade frontend.
- PostgreSQL as operational database.
- RDF/OWL file remains read-only knowledge source.
- Recommendation should degrade gracefully if RDF is invalid or missing.
- Tests should cover critical student flow and ontology availability.
- Existing features must not be rebuilt unnecessarily.
- Use `KEEP / FIX / EXTEND / ADD` as the implementation principle.

## 10. Acceptance Criteria

- A new student can register and reach career profile.
- Initial profile can be completed without generating final recommendations.
- Career exploration is available before post-assessment but hides final scores.
- Career list is paginated, not progressive load-more.
- Student can open and complete modules.
- Student can complete assessment and produce competency evidence.
- After assessment, recommendations are stored.
- Top recommendation includes explainable strengths, gaps, readiness, and ontology trace.
- Student can select a target career only after post-assessment.
- Learning path is generated for target career.
- `php artisan test` passes.

