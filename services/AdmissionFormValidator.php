<?php

class AdmissionFormValidator
{
    public function buildFormData(array $post, array $user): array
    {
        return [
            'last_name' => sanitizeInput($post['last_name'] ?? ''),
            'first_name' => sanitizeInput($post['first_name'] ?? ''),
            'middle_name' => sanitizeInput($post['middle_name'] ?? ''),
            'name_extension' => sanitizeInput($post['name_extension'] ?? ''),
            'sex' => sanitizeInput($post['sex'] ?? ''),
            'gender' => sanitizeInput($post['gender'] ?? ''),
            'gender_specify' => sanitizeInput($post['gender_specify'] ?? ''),
            'civil_status' => sanitizeInput($post['civil_status'] ?? ''),
            'spouse_name' => sanitizeInput($post['spouse_name'] ?? ''),
            'age' => isset($post['age']) ? (int) $post['age'] : 0,
            'birth_date' => sanitizeInput($post['birth_date'] ?? ''),
            'birth_place' => sanitizeInput($post['birth_place'] ?? ''),
            'pwd' => isset($post['pwd']) ? 1 : 0,
            'disability' => sanitizeInput($post['disability'] ?? ''),
            'ethnic_affiliation' => sanitizeInput($post['ethnic_affiliation'] ?? ''),
            'ethnic_others_specify' => sanitizeInput($post['ethnic_others_specify'] ?? ''),
            'home_address' => sanitizeInput($post['home_address'] ?? ''),
            'mobile_number' => '+63' . sanitizeInput($post['mobile_number'] ?? ''),
            'email_address' => sanitizeInput($post['email_address'] ?? ''),
            'father_name' => sanitizeInput($post['father_name'] ?? ''),
            'father_occupation' => sanitizeInput($post['father_occupation'] ?? ''),
            'father_contact' => !empty($post['father_contact'] ?? '') ? '+63' . sanitizeInput($post['father_contact']) : '',
            'mother_name' => sanitizeInput($post['mother_name'] ?? ''),
            'mother_occupation' => sanitizeInput($post['mother_occupation'] ?? ''),
            'mother_contact' => !empty($post['mother_contact'] ?? '') ? '+63' . sanitizeInput($post['mother_contact']) : '',
            'guardian_name' => sanitizeInput($post['guardian_name'] ?? ''),
            'guardian_occupation' => sanitizeInput($post['guardian_occupation'] ?? ''),
            'guardian_contact' => !empty($post['guardian_contact'] ?? '') ? '+63' . sanitizeInput($post['guardian_contact']) : '',
            'last_school' => sanitizeInput($post['last_school'] ?? ''),
            'school_address' => sanitizeInput($post['school_address'] ?? ''),
            'year_last_attended' => ($user['student_type'] ?? '') === 'Transferee' && !empty($post['year_last_attended'] ?? '')
                ? sanitizeInput($post['year_last_attended'])
                : null,
            'strand_taken' => sanitizeInput($post['strand_taken'] ?? ''),
            'year_graduated' => sanitizeInput($post['year_graduated'] ?? ''),
            'course_first' => sanitizeInput($post['course_first'] ?? ''),
            'course_second' => sanitizeInput($post['course_second'] ?? ''),
            'course_third' => sanitizeInput($post['course_third'] ?? ''),
        ];
    }

    public function validateTransfereeSpecific(array $post, array $user): ?string
    {
        if (($user['student_type'] ?? '') === 'Transferee' && empty($post['year_last_attended'] ?? '')) {
            return 'Year Last Attended is required for transferee students';
        }

        return null;
    }
}
