<?php

class SiblingManager
{
    private F2PersonalDataForm $f2Form;

    public function __construct(F2PersonalDataForm $f2Form)
    {
        $this->f2Form = $f2Form;
    }

    public function saveSiblingsFromPost(int $studentId, array $postSiblings): array
    {
        $siblingsData = array();

        if (isset($postSiblings) && is_array($postSiblings)) {
            foreach ($postSiblings as $sibling) {
                if (isset($sibling['name']) && trim($sibling['name']) !== '') {
                    $siblingsData[] = array(
                        'name'  => sanitizeInput($sibling['name']),
                        'order' => isset($sibling['order']) ? (int) $sibling['order'] : 0,
                    );
                }
            }
        }

        return $this->f2Form->saveSiblings($studentId, $siblingsData);
    }
}
