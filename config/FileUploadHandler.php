<?php
/**
 * Secure File Upload Handler
 * 
 * Handles file uploads with proper validation and security checks
 */

class FileUploadHandler {
    private $uploadDir;
    private $maxFileSize;
    private $allowedMimeTypes;
    private $allowedExtensions;
    private $errors = [];
    
    public function __construct() {
        $this->uploadDir = UPLOAD_DIR;
        $this->maxFileSize = MAX_FILE_SIZE;
        $this->allowedMimeTypes = ALLOWED_IMAGE_TYPES;
        $this->allowedExtensions = ALLOWED_IMAGE_EXTENSIONS;
        
        // Ensure upload directory exists
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    /**
     * Upload a file with validation
     * 
     * @param array $file The $_FILES array element
     * @param string $prefix Prefix for the filename
     * @return array ['success' => bool, 'filename' => string, 'errors' => array]
     */
    public function upload($file, $prefix = 'file') {
        $this->errors = [];
        
        // Check if file was uploaded
        if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            $this->errors[] = "No file was uploaded";
            return $this->returnResult(false);
        }
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = $this->getUploadErrorMessage($file['error']);
            return $this->returnResult(false);
        }
        
        // Validate file size
        if ($file['size'] > $this->maxFileSize) {
            $this->errors[] = "File size exceeds maximum allowed size of " . ($this->maxFileSize / 1024 / 1024) . "MB";
            return $this->returnResult(false);
        }
        
        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            $this->errors[] = "Invalid file type. Only images (JPEG, PNG, GIF) are allowed";
            return $this->returnResult(false);
        }
        
        // Validate file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            $this->errors[] = "Invalid file extension. Allowed: " . implode(', ', $this->allowedExtensions);
            return $this->returnResult(false);
        }
        
        // Verify it's actually an image
        if (!$this->isValidImage($file['tmp_name'])) {
            $this->errors[] = "File is not a valid image";
            return $this->returnResult(false);
        }
        
        // Generate secure filename
        $filename = $this->generateSecureFilename($prefix, $extension);
        $destination = $this->uploadDir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $this->errors[] = "Failed to move uploaded file";
            return $this->returnResult(false);
        }
        
        return $this->returnResult(true, $filename);
    }
    
    /**
     * Delete a file
     * 
     * @param string $filename The filename to delete
     * @return bool Success status
     */
    public function delete($filename) {
        if (empty($filename)) {
            return false;
        }
        
        $filepath = $this->uploadDir . $filename;
        
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        
        return false;
    }
    
    /**
     * Verify if file is a valid image
     */
    private function isValidImage($filepath) {
        $imageInfo = @getimagesize($filepath);
        return $imageInfo !== false;
    }
    
    /**
     * Generate secure filename
     */
    private function generateSecureFilename($prefix, $extension) {
        return $prefix . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    }
    
    /**
     * Get upload error message
     */
    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return "File size exceeds maximum allowed size";
            case UPLOAD_ERR_PARTIAL:
                return "File was only partially uploaded";
            case UPLOAD_ERR_NO_TMP_DIR:
                return "Missing temporary folder";
            case UPLOAD_ERR_CANT_WRITE:
                return "Failed to write file to disk";
            case UPLOAD_ERR_EXTENSION:
                return "File upload stopped by extension";
            default:
                return "Unknown upload error";
        }
    }
    
    /**
     * Return result array
     */
    private function returnResult($success, $filename = null) {
        return [
            'success' => $success,
            'filename' => $filename,
            'errors' => $this->errors
        ];
    }
    
    /**
     * Get errors
     */
    public function getErrors() {
        return $this->errors;
    }
}
?>
