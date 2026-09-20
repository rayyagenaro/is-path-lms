<?php

namespace App\Domains\Career\Services;

final class ModuleGuide
{
    public function find(string $courseCode, string $title): ?array
    {
        $courses = require resource_path('learning/guides.php');
        foreach (explode("\n", $courses[$courseCode] ?? '') as $line) {
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) === 3 && $parts[0] === $title) {
                return ['summary' => $parts[1], 'exercise' => $parts[2], 'version' => '2026-09'];
            }
        }

        return null;
    }
}
