<?php

namespace App\Domains\Ontology\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

final class OntologyRepository
{
    public function summary(): array
    {
        $path = (string) config('ispath.ontology.path');
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException("Ontology tidak dapat dibaca: {$path}");
        }

        $hash = hash_file('sha256', $path);
        return Cache::remember('ispath.ontology.'.$hash, config('ispath.ontology.cache_seconds', 3600), function () use ($path, $hash) {
            $document = new DOMDocument();
            $previous = libxml_use_internal_errors(true);
            $loaded = $document->load($path, LIBXML_NONET | LIBXML_NOBLANKS);
            $errors = libxml_get_errors();
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            if (!$loaded) {
                throw new RuntimeException('RDF/XML tidak valid: '.($errors[0]->message ?? 'format tidak dikenali'));
            }

            $xpath = new DOMXPath($document);
            foreach ($this->namespaces() as $prefix => $uri) $xpath->registerNamespace($prefix, $uri);

            $rules = [];
            $nodes = $xpath->query("//rdf:Description[rdf:type[contains(@rdf:resource, 'swrl#Imp')]]");
            foreach ($nodes ?: [] as $node) {
                $label = trim((string) $xpath->evaluate('string(rdfs:label)', $node));
                $enabled = strtolower(trim((string) $xpath->evaluate('string(swrla:isRuleEnabled)', $node)));
                if ($label !== '') $rules[$label] = in_array($enabled, ['true', '1'], true);
            }

            return [
                'source' => $path,
                'hash' => $hash,
                'modified_at' => date(DATE_ATOM, filemtime($path)),
                'namespace' => (string) config('ispath.ontology.namespace'),
                'classes' => $xpath->query('//owl:Class[@rdf:about]')?->length ?? 0,
                'object_properties' => $xpath->query('//owl:ObjectProperty[@rdf:about]')?->length ?? 0,
                'data_properties' => $xpath->query('//owl:DatatypeProperty[@rdf:about]')?->length ?? 0,
                'named_individuals' => $xpath->query("//owl:NamedIndividual | //rdf:Description[rdf:type[contains(@rdf:resource, 'owl#NamedIndividual')]]")?->length ?? 0,
                'rules' => $rules,
                'enabled_rule_count' => count(array_filter($rules)),
            ];
        });
    }

    public function hasEnabledRule(string $label): bool
    {
        return ($this->summary()['rules'][$label] ?? false) === true;
    }

    private function namespaces(): array
    {
        return [
            'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
            'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
            'owl' => 'http://www.w3.org/2002/07/owl#',
            'swrl' => 'http://www.w3.org/2003/11/swrl#',
            'swrla' => 'http://swrl.stanford.edu/ontologies/3.3/swrla.owl#',
        ];
    }
}
