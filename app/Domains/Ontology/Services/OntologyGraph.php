<?php
namespace App\Domains\Ontology\Services;

final class OntologyGraph
{
    /** Returns true when assigning $newParent to $node would create a cycle. */
    public function wouldCreateCycle(int $node, ?int $newParent, array $parents): bool
    {
        $seen = [$node => true];
        while ($newParent !== null) {
            if (isset($seen[$newParent])) return true;
            $seen[$newParent] = true;
            $newParent = $parents[$newParent] ?? null;
        }
        return false;
    }
}
