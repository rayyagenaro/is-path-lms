# IS-Path Roadmap and Gap Analysis

## 1. P0 Already Implemented

- Student-only app direction.
- Register/login/guest preview.
- Student career profile.
- Career exploration and detail pages.
- Career listing pagination.
- Course catalog with 26 courses.
- Module opening and completion.
- Assessment attempt flow.
- Assessment grading into competency evidence.
- Student competency calculation.
- Recommendation workflow after post-assessment.
- Explainable recommendation snapshots.
- RDF ontology metadata loading.
- SWRL application bridge for selected rules.
- Personalized learning path regeneration.
- Project submission storage.
- Automated tests for critical flows.

## 2. P0 Remaining Polish

- Expand assessment question bank from 6 core competencies to all 40 competencies.
- Add better empty states for users who have no target, no progress, or no evidence.
- Add visible history for recommendation changes over time.
- Add clearer status labels on project submission after review is introduced.
- Verify responsive screenshots after each major UI pass.

## 3. P1 Recommended Next Work

- Project-to-competency validation:
  - allow a project submission to generate competency evidence after review or automated rubric scoring.

- Assessment expansion:
  - add per-competency question pools,
  - vary difficulty by proficiency level,
  - map each answer to competency gain and confidence.

- Learning path intelligence:
  - explain why each course/module appears,
  - mark prerequisite blockers,
  - show reused competencies across careers.

- Ontology alignment:
  - document every RDF rule with app behavior,
  - add test cases for each mapped rule,
  - add a coverage report: RDF rule exists, app bridge mapped, test exists.

- Recommendation quality:
  - add interpretation copy for low scores,
  - show top strengths and blocking gaps side by side,
  - store previous recommendation runs for comparison.

## 4. P2 Optional Future Work

- Instructor role for assessment/project review.
- Admin role for managing competency taxonomy and course mappings.
- Curriculum committee workflow for rule version approval.
- Full external ontology reasoner service.
- Analytics dashboard.
- Notification system.
- Exportable student competency report.

These were present in earlier PRDs, but they are not active current scope unless the product owner reopens multi-role workflows.

## 5. Main Product Risks

- Recommendation trust risk: scores can feel wrong if assessment coverage is too narrow.
- Ontology expectation risk: stakeholders may assume full SWRL execution, while current implementation is an application bridge.
- Content quality risk: weak assessment items can reduce academic credibility.
- Scope risk: older PRDs mention admin/instructor roles, but user direction changed to student-only.
- UX risk: adding too many score panels before post-assessment can make the flow feel judgmental or confusing.

## 6. Technical Risks

- `migrate:fresh` removes seeded competency/course data unless seeders are rerun.
- PostgreSQL PHP driver must be enabled for local Laravel runtime.
- RDF path or namespace changes require config cache clear.
- Large RDF file should remain read-only and cached.
- Avoid changing database structure unless necessary for PRD alignment.

## 7. Suggested Next Implementation Order

1. Expand assessment bank to 40 competencies.
2. Add rule coverage matrix for RDF/SWRL mapping.
3. Improve project evidence workflow.
4. Add recommendation history UI.
5. Add learning path explanation per item.
6. Add screenshot-based UI verification after meaningful interface changes.

