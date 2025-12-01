<?php

class FamilyInfoHandler
{
    public function buildFamilyInfo(array $post): array
    {
        return array(
            'father_name'              => sanitizeInput($post['father_name'] ?? ''),
            'father_occupation'        => sanitizeInput($post['father_occupation'] ?? ''),
            'father_ethnicity'         => sanitizeInput($post['father_ethnicity'] ?? ''),
            'mother_name'              => sanitizeInput($post['mother_name'] ?? ''),
            'mother_occupation'        => sanitizeInput($post['mother_occupation'] ?? ''),
            'mother_ethnicity'         => sanitizeInput($post['mother_ethnicity'] ?? ''),
            'parents_living_together'  => sanitizeInput($post['parents_living_together'] ?? ''),
            'parents_separated'        => sanitizeInput($post['parents_separated'] ?? ''),
            'separation_reason'        => sanitizeInput($post['separation_reason'] ?? ''),
            'living_with'              => sanitizeInput($post['living_with'] ?? ''),
            'age_when_separated'       => sanitizeInput($post['age_when_separated'] ?? ''),
            'guardian_name'            => sanitizeInput($post['guardian_name'] ?? ''),
            'guardian_relationship'    => sanitizeInput($post['guardian_relationship'] ?? ''),
            'guardian_address'         => sanitizeInput($post['guardian_address'] ?? ''),
            'guardian_contact_number'  => sanitizeInput($post['guardian_contact_number'] ?? ''),
        );
    }
}
