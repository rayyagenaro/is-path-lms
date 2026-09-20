# AI Working Contract for IS-Path

## 1. Prime Directive

Do not rebuild IS-Path. Continue the existing Laravel, Blade, PostgreSQL, CSS, and domain service architecture.

Use this operating mode:

- KEEP what works.
- FIX broken or inconsistent behavior.
- EXTEND existing modules when the feature naturally belongs there.
- ADD new files only when the current structure does not already have the right place.

## 2. Current Product Truth

- The app is student-only.
- The student is looking for a learning path and career direction.
- Career recommendations are locked until post-assessment.
- Ontology supports recommendation explainability and rule trace.
- PostgreSQL remains the source of operational truth.
- RDF/OWL remains the source of domain knowledge.

## 3. Do Not Do

- Do not reintroduce lecturer/admin UI unless explicitly requested.
- Do not show final career match/readiness before post-assessment.
- Do not treat self-rating as verified evidence.
- Do not replace Blade with React/Vue/Next.
- Do not change visual identity away from Poppins, white/off-white, forest, emerald, calm editorial.
- Do not add generic AI-looking cards, gradients, icons, or copy.
- Do not copy the whole RDF graph into relational tables.
- Do not claim generic SWRL reasoning unless a real reasoner is implemented.

## 4. UX Contract

Follow `../../DESIGN.md`.

Important rules:

- Keep controls at least 44px high.
- Use consistent button sizing.
- Use restrained motion.
- Keep dashboards useful and calm.
- Avoid card soup.
- Use pagination or clear progressive disclosure for long lists.
- Preserve Indonesian product language.
- ASCII animation is login-only.

## 5. Engineering Contract

Before changing code:

- inspect the relevant controller, view, service, route, migration, seeder, and tests,
- understand current behavior,
- edit only the required files,
- preserve user changes,
- add or update tests when behavior changes.

After changing code:

- run focused tests,
- run `php artisan test` when the change touches shared flow,
- report exact files and verification result.

## 6. Source Priority

Use this priority order when sources conflict:

1. Latest explicit user direction in chat.
2. Current implemented behavior that is working.
3. This consolidated PRD pack.
4. `PRDLMS.md`.
5. `IS-Path-PRD-v2.md`.
6. `PRD-Competency-Based-LMS-Sistem-Informasi.md`.
7. `IS-PATH-ONTOLOGY.md`.
8. Raw RDF details in `Ontologi/LMS FIX.rdf`.

The raw RDF is authoritative for ontology terms and rule identifiers, but the app architecture document is authoritative for how Laravel currently consumes it.

