<?php

class ApplicantFilterEngine
{
    public function getFiltersFromArray(array $source): array
    {
        $status = isset($source['status']) ? sanitizeInput($source['status']) : '';
        $type   = isset($source['type']) ? sanitizeInput($source['type']) : '';
        $search = isset($source['search']) ? sanitizeInput($source['search']) : '';

        return [
            'status' => $status,
            'type'   => $type,
            'search' => $search,
        ];
    }
}
