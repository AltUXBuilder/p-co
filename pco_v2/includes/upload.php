<?php
/**
 * Prescribe & Co. — Image Upload Handler
 * Usage: $result = handle_image_upload('product_image', 'products');
 */

function handle_image_upload(string $field, string $folder = 'products'): array {
    $allowed   = ['jpg','jpeg','png','webp'];
    $maxBytes  = 5 * 1024 * 1024; // 5 MB
    $uploadDir = APP_PATH . '/uploads/' . $folder . '/';

    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => null, 'path' => null]; // no file — not an error
    }

    $file = $_FILES[$field];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $msgs = [
            UPLOAD_ERR_INI_SIZE   => 'File too large (server limit).',
            UPLOAD_ERR_FORM_SIZE  => 'File too large.',
            UPLOAD_ERR_PARTIAL    => 'Upload was incomplete.',
            UPLOAD_ERR_NO_TMP_DIR => 'No temp directory.',
            UPLOAD_ERR_CANT_WRITE => 'Cannot write file.',
        ];
        return ['ok' => false, 'error' => $msgs[$file['error']] ?? 'Upload error.', 'path' => null];
    }

    if ($file['size'] > $maxBytes) {
        return ['ok' => false, 'error' => 'Image must be under 5 MB.', 'path' => null];
    }

    // Validate extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        return ['ok' => false, 'error' => 'Only JPG, PNG and WebP images are allowed.', 'path' => null];
    }

    // Validate actual mime type (don't trust $_FILES['type'])
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    $validMimes = ['image/jpeg','image/png','image/webp','image/jpg'];
    if (!in_array($mime, $validMimes)) {
        return ['ok' => false, 'error' => 'Invalid image file.', 'path' => null];
    }

    // Generate unique filename
    $filename = uniqid($folder . '_', true) . '.' . $ext;
    $destPath = $uploadDir . $filename;

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['ok' => false, 'error' => 'Failed to save image. Check uploads/ directory permissions.', 'path' => null];
    }

    // Return relative web path
    $webPath = '/uploads/' . $folder . '/' . $filename;
    return ['ok' => true, 'error' => null, 'path' => $webPath];
}

function delete_upload(string $path): void {
    if (!$path) return;
    $full = APP_PATH . $path;
    if (file_exists($full) && strpos($full, APP_PATH . '/uploads/') === 0) {
        @unlink($full);
    }
}

function img_tag(string $path = null, string $alt = '', string $class = '', string $style = ''): string {
    if (!$path) return '';
    $src = APP_URL . htmlspecialchars($path, ENT_QUOTES);
    return "<img src=\"$src\" alt=\"" . htmlspecialchars($alt) . "\" class=\"$class\" style=\"$style\" loading=\"lazy\">";
}
