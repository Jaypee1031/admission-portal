<?php

class AutoFillEngine
{
    public function buildInitialFormData(
        array $existingData,
        array $admissionFormData,
        array $user,
        string $studentFullName,
        ?string $studentGWA,
        ?string $f2GWA
    ): array {
        if (!empty($existingData)) {
            $formData = $existingData;

            if (empty($formData['declaration']['signature_over_printed_name'] ?? '')) {
                $formData['declaration']['signature_over_printed_name'] = $studentFullName;
            }

            return $formData;
        }

        $age = null;
        if (!empty($admissionFormData['birth_date'])) {
            $birthDate = new DateTime($admissionFormData['birth_date']);
            $today     = new DateTime();
            $age       = $today->diff($birthDate)->y;
        }

        return array(
            'personal_info' => array(
                'last_name'               => $admissionFormData['last_name'] ?? ($user['last_name'] ?? ''),
                'first_name'              => $admissionFormData['first_name'] ?? ($user['first_name'] ?? ''),
                'middle_name'             => $admissionFormData['middle_name'] ?? ($user['middle_name'] ?? ''),
                'civil_status'            => $admissionFormData['civil_status'] ?? '',
                'spouse_name'             => $admissionFormData['spouse_name'] ?? '',
                'course_year_level'       => '1st Year',
                'sex'                     => $admissionFormData['sex'] ?? '',
                'ethnicity'               => $admissionFormData['ethnic_affiliation'] ?? '',
                'ethnicity_others_specify'=> $admissionFormData['ethnic_others_specify'] ?? '',
                'date_of_birth'           => $admissionFormData['birth_date'] ?? '',
                'age'                     => $age,
                'place_of_birth'          => $admissionFormData['birth_place'] ?? '',
                'religion'                => '',
                'address'                 => $admissionFormData['home_address'] ?? '',
                'contact_number'          => $admissionFormData['mobile_number'] ?? '',
            ),
            'family_info' => array(
                'father_name'             => $admissionFormData['father_name'] ?? '',
                'father_occupation'       => $admissionFormData['father_occupation'] ?? '',
                'father_ethnicity'        => '',
                'mother_name'             => $admissionFormData['mother_name'] ?? '',
                'mother_occupation'       => $admissionFormData['mother_occupation'] ?? '',
                'mother_ethnicity'        => '',
                'parents_living_together' => '',
                'parents_separated'       => '',
                'separation_reason'       => '',
                'living_with'             => '',
                'age_when_separated'      => '',
                'guardian_name'           => $admissionFormData['guardian_name'] ?? '',
                'guardian_relationship'   => '',
                'guardian_address'        => '',
                'guardian_contact_number' => $admissionFormData['guardian_contact'] ?? '',
                'siblings_info'           => '',
            ),
            'education' => array(
                'elementary_school'               => '',
                'secondary_school'               => '',
                'school_university_last_attended'=> $admissionFormData['last_school'] ?? '',
                'school_name'                    => $admissionFormData['last_school'] ?? '',
                'school_address'                 => $admissionFormData['school_address'] ?? '',
                'general_average'                => $f2GWA ?: $studentGWA,
                'course_first_choice'            => $admissionFormData['course_first'] ?? '',
                'course_second_choice'           => $admissionFormData['course_second'] ?? '',
                'course_third_choice'            => $admissionFormData['course_third'] ?? '',
                'parents_choice'                 => '',
                'nature_of_schooling_continuous' => '',
                'reason_if_interrupted'          => '',
            ),
            'skills' => array(
                'talents' => '',
                'awards'  => '',
                'hobbies' => '',
            ),
            'health_record' => array(
                'disability_specify'     => $admissionFormData['disability'] ?? '',
                'confined_rehabilitated' => '',
                'confined_when'          => '',
                'treated_for_illness'    => '',
                'treated_when'           => '',
            ),
            'declaration' => array(
                'signature_over_printed_name' => $studentFullName,
                'date_accomplished'           => date('Y-m-d'),
            ),
        );
    }
}
