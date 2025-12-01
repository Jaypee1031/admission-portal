<?php

class AdmissionFormFileUploader
{
    private AdmissionForm $admissionForm;
    private FileUploader $fileUploader;

    public function __construct(AdmissionForm $admissionForm, FileUploader $fileUploader)
    {
        $this->admissionForm = $admissionForm;
        $this->fileUploader  = $fileUploader;
    }

    public function handleProfilePhoto(int $studentId, array $formData, array $post, array $files): array
    {
        if (isset($post['remove_photo']) && $post['remove_photo'] == '1') {
            $oldFormData = $this->admissionForm->getAdmissionForm($studentId);
            if ($oldFormData && !empty($oldFormData['profile_photo'])) {
                $oldPhotoPath = '../' . $oldFormData['profile_photo'];
                if (file_exists($oldPhotoPath)) {
                    unlink($oldPhotoPath);
                    error_log('Removed profile photo: ' . $oldPhotoPath);
                }
            }
            $formData['profile_photo'] = null;
        }

        if (!isset($files['profile_photo'])) {
            return $formData;
        }

        $photoFile = $files['profile_photo'];

        if ($photoFile['error'] === UPLOAD_ERR_OK) {
            $uploadDir       = '../uploads/profile_photos/';
            $allowedExts     = ['jpg', 'jpeg', 'png', 'gif'];
            $maxFileSize     = 8 * 1024 * 1024; // 8MB

            $oldFormData = $this->admissionForm->getAdmissionForm($studentId);
            if ($oldFormData && !empty($oldFormData['profile_photo'])) {
                $oldPhotoPath = '../' . $oldFormData['profile_photo'];
                if (file_exists($oldPhotoPath)) {
                    unlink($oldPhotoPath);
                    error_log('Deleted old profile photo: ' . $oldPhotoPath);
                }
            }

            $result = $this->fileUploader->uploadProfilePhoto($photoFile, $uploadDir, $studentId, $maxFileSize, $allowedExts);

            if ($result['success']) {
                $formData['profile_photo'] = $result['relative_path'];
                error_log('Profile photo uploaded successfully (replaced existing): ' . $result['relative_path']);
            } else {
                if (!empty($result['message'])) {
                    showAlert($result['message'], 'error');
                }
            }
        } elseif ($photoFile['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE   => 'File is too large (server limit)',
                UPLOAD_ERR_FORM_SIZE  => 'File is too large (form limit)',
                UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION  => 'File upload stopped by extension',
            ];

            $errorCode    = $photoFile['error'];
            $errorMessage = $uploadErrors[$errorCode] ?? 'Unknown upload error';
            showAlert('Photo upload failed: ' . $errorMessage, 'error');
            error_log('Photo upload error: ' . $errorMessage . ' (Code: ' . $errorCode . ')');
        }

        return $formData;
    }
}
