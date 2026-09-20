<?php

return [
    'ontology' => [
        'path' => env('ISPATH_ONTOLOGY_PATH', base_path('Ontologi/LMS FIX.rdf')),
        'namespace' => env('ISPATH_ONTOLOGY_NAMESPACE', 'http://www.semanticweb.org/hp/ontologies/2026/8/untitled-ontology-79#'),
        'cache_seconds' => (int) env('ISPATH_ONTOLOGY_CACHE_SECONDS', 3600),
    ],
];
