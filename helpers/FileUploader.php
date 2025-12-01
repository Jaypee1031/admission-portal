<?php

class FileUploader
{
    public function uploadProfilePhoto(array $file, string $uploadDir, int $studentId, int $maxFileSize, array $allowedExtensions): array
    {
        $result = [
            'success'       => false,
            'relative_path' => null,
            'full_path'     => null,
            'message'       => null,
        ];

        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $result['message'] = 'No file uploaded';
            return $result;
        }

        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                $result['message'] = 'Failed to create upload directory';
                error_log('Failed to create upload directory: ' . $uploadDir);
                return $result;
            }
        }

        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($file['size'] > $maxFileSize) {
            $result['message'] = 'File size must be less than 8MB';
            return $result;
        }

        if (!in_array($fileExtension, $allowedExtensions, true)) {
            $result['message'] = 'Invalid file type. Please upload JPG, PNG, or GIF image.';
            return $result;
        }

        $fileName = 'profile_' . $studentId . '.' . $fileExtension;
        $filePath = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            $result['message'] = 'Failed to upload profile photo. Please try again.';
            error_log('Failed to move uploaded file to: ' . $filePath);
            return $result;
        }

        $result['success']       = true;
        $result['relative_path'] = 'uploads/profile_photos/' . $fileName;
        $result['full_path']     = $filePath;

        return $result;
    }
}
