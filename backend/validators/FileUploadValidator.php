<?php

declare(strict_types=1);

namespace Cidb\Backend\Validators;

final class FileUploadValidator extends AbstractValidator
{
    private const DANGEROUS_EXTENSIONS = [
        'php', 'phtml', 'phar', 'cgi', 'pl', 'asp', 'aspx', 'js', 'exe', 'bat', 'cmd', 'sh', 'com', 'scr', 'jar',
    ];

    /**
     * @param array<string, mixed> $options
     */
    public function validate(mixed $input, array $options = []): ValidationResult
    {
        $file = is_array($input) ? $input : [];
        $field = (string) ($options['field'] ?? 'file');
        $allowedMimeTypes = array_values(array_map('strval', $options['allowed_mime_types'] ?? ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'application/pdf']));
        $allowedExtensions = array_values(array_map('strtolower', $options['allowed_extensions'] ?? ['jpg', 'jpeg', 'png', 'webp', 'pdf']));
        $maxBytes = (int) ($options['max_bytes'] ?? 10 * 1024 * 1024);

        if ($file === []) {
            return $this->invalid($field, 'file_required', 'Uploaded file is required.');
        }

        $name = $this->normalizedString($file['name'] ?? '');
        $type = $this->normalizedString($file['type'] ?? '');
        $tmpName = $this->normalizedString($file['tmp_name'] ?? '');
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        $size = (int) ($file['size'] ?? 0);

        if ($error !== UPLOAD_ERR_OK) {
            return $this->invalid($field, 'file_upload_error', 'File upload failed.', $name, ['php_upload_error' => $error]);
        }

        if ($name === '') {
            return $this->invalid($field, 'file_name_required', 'File name is required.');
        }

        if (preg_match('/[\/\\\\\x00]/', $name) === 1) {
            return $this->invalid($field, 'file_name_invalid', 'File name contains invalid path characters.', $name);
        }

        $basename = basename($name);
        $extension = strtolower(pathinfo($basename, PATHINFO_EXTENSION));

        if ($extension === '') {
            return $this->invalid($field, 'file_extension_missing', 'File extension is missing.', $name);
        }

        if (!in_array($extension, $allowedExtensions, true)) {
            return $this->invalid(
                $field,
                'file_extension_invalid',
                'File extension is not allowed.',
                $name,
                ['allowed_extensions' => $allowedExtensions]
            );
        }

        $segments = array_values(array_filter(explode('.', $basename), static fn (string $segment): bool => $segment !== ''));
        if (count($segments) > 2) {
            $prefixSegments = array_slice($segments, 0, -1);
            foreach ($prefixSegments as $segment) {
                if (in_array(mb_strtolower($segment), self::DANGEROUS_EXTENSIONS, true)) {
                    return $this->invalid($field, 'file_double_extension', 'Double extensions are not allowed.', $name);
                }
            }
        }

        if ($size <= 0) {
            return $this->invalid($field, 'file_empty', 'Uploaded file is empty.', $name);
        }

        if ($size > $maxBytes) {
            return $this->invalid(
                $field,
                'file_too_large',
                'Uploaded file exceeds the allowed size limit.',
                $name,
                ['max_bytes' => $maxBytes, 'actual_bytes' => $size]
            );
        }

        if ($type !== '' && !in_array($type, $allowedMimeTypes, true)) {
            return $this->invalid(
                $field,
                'mime_type_invalid',
                'File MIME type is not allowed.',
                $type,
                ['allowed_mime_types' => $allowedMimeTypes]
            );
        }

        if (is_file($tmpName)) {
            $detectedMimeType = $this->detectMimeType($tmpName, $extension);
            if ($detectedMimeType !== null && !in_array($detectedMimeType, $allowedMimeTypes, true)) {
                return $this->invalid(
                    $field,
                    'mime_type_mismatch',
                    'Detected MIME type does not match the allowed policy.',
                    $detectedMimeType,
                    ['allowed_mime_types' => $allowedMimeTypes]
                );
            }

            if ($this->looksMalicious($tmpName)) {
                return $this->invalid($field, 'malware_suspected', 'Uploaded file failed the malware placeholder check.', $name);
            }
        }

        return ValidationResult::success([
            'file_name' => $basename,
            'file_extension' => $extension,
            'mime_type' => $type,
            'size_bytes' => $size,
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $files
     * @param array<string, array<string, mixed>> $policies
     */
    public function validateBatch(array $files, array $policies = []): ValidationResult
    {
        $result = ValidationResult::success();

        foreach ($files as $key => $file) {
            $policy = $policies[$key] ?? [];
            $policy['field'] = is_string($key) ? $key : ('file_' . $key);
            $result->merge($this->validate($file, $policy));
        }

        return $result;
    }

    private function detectMimeType(string $path, string $extension): ?string
    {
        if (function_exists('mime_content_type')) {
            $mime = @mime_content_type($path);
            if (is_string($mime) && $mime !== '') {
                return $mime;
            }
        }

        if (class_exists('finfo')) {
            $finfo = @new \finfo(FILEINFO_MIME_TYPE);
            if ($finfo instanceof \finfo) {
                $mime = @$finfo->file($path);
                if (is_string($mime) && $mime !== '') {
                    return $mime;
                }
            }
        }

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) && function_exists('exif_imagetype')) {
            $imageType = @\exif_imagetype($path);
            return match ($imageType) {
                IMAGETYPE_JPEG => 'image/jpeg',
                IMAGETYPE_PNG => 'image/png',
                IMAGETYPE_WEBP => 'image/webp',
                default => null,
            };
        }

        if ($extension === 'pdf') {
            $handle = @\fopen($path, 'rb');
            if ($handle !== false) {
                $head = (string) \fread($handle, 4);
                \fclose($handle);
                if ($head === '%PDF') {
                    return 'application/pdf';
                }
            }
        }

        return null;
    }

    private function looksMalicious(string $path): bool
    {
        $handle = @\fopen($path, 'rb');
        if ($handle === false) {
            return true;
        }

        $content = (string) \fread($handle, 2048);
        \fclose($handle);

        return str_contains($content, '<?php')
            || str_contains($content, '<?=') 
            || str_contains($content, '<script')
            || str_contains($content, 'MZ');
    }
}
