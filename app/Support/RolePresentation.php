<?php

namespace App\Support;

final class RolePresentation
{
    public static function initials(string $roleName): string
    {
        $known = [
            'Data Analyst' => 'DA',
            'Business Intelligence Analyst' => 'BI',
            'Data Engineer' => 'DE',
            'Data Scientist' => 'DS',
            'Database Administrator' => 'DB',
            'Business Analyst' => 'BA',
            'System Analyst' => 'SA',
            'Product Manager / Product Owner' => 'PM',
            'ERP / CRM Consultant' => 'EC',
            'IT / Digital Consultant' => 'IC',
            'Software / Web Developer' => 'SW',
            'QA Engineer / Software Tester' => 'QA',
            'UI/UX Designer' => 'UX',
            'System / Network Administrator' => 'SN',
            'Cloud / DevOps Engineer' => 'CD',
            'Cybersecurity Analyst' => 'CA',
            'IT Auditor / GRC Analyst' => 'IA',
            'IT Project Manager / Scrum Master' => 'PM',
            'IT Service Management / IT Support' => 'IT',
            'Solution Architect' => 'SA',
            'AI / Automation Analyst' => 'AI',
            'Pre-Sales / Solution Consultant' => 'PS',
        ];
        if (isset($known[$roleName])) return $known[$roleName];

        preg_match_all('/[[:alnum:]]+/u', $roleName, $matches);
        return collect($matches[0])->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
    }
}
