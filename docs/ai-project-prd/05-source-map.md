# Source Map

## Consolidated Sources

This PRD pack reconciles the following files:

- `../../PRD-Competency-Based-LMS-Sistem-Informasi.md`
- `../../IS-Path-PRD-v2.md`
- `../../PRDLMS.md`
- `../../IS-PATH-ONTOLOGY.md`
- `../../Ontologi/LMS FIX.rdf`
- `../../docs/ontology-integration.md`
- `../../DESIGN.md`
- current Laravel routes, seeders, domain services, and tests.

The `sources/` folder contains local copies of these product/reference sources so a future AI agent can work from this folder first.

## Key Reconciliations

- Earlier PRDs included lecturer, admin, and committee roles. Current user direction narrows active scope to student only.
- Earlier PRDs discussed ontology management broadly. Current implementation uses RDF/OWL read-only plus a Laravel SWRL application bridge.
- PRD v2 expanded the domain into 8 clusters, 22 career roles, 40 competencies, 26 courses, and module-level learning. This is now seeded and tested.
- Current flow separates initial exploration from final recommendation. Final scoring is only after post-assessment.
- The design system was created through frontend polish sessions and is now a required constraint for future UI work.

## Current Runtime Evidence

Latest observed route count:

- 40 routes.

Latest observed ontology summary:

- 26 classes.
- 88 object properties.
- 74 data properties.
- 786 named individuals.
- 24 enabled rules.

Latest observed tests:

- 22 passed.
- 126 assertions.
