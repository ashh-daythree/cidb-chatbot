<?php

declare(strict_types=1);

namespace Cidb\Backend\Validators;

final class SignatureValidator extends AbstractValidator
{
    public function validate(mixed $input): ValidationResult
    {
        $dataUrl = $this->normalizedString(is_array($input) ? ($input['signature'] ?? $input['data_url'] ?? $input['base64'] ?? '') : $input);

        if ($dataUrl === '') {
            return $this->invalid('signature', 'signature_required', 'Signature is required.');
        }

        if (!preg_match('#^(data:image/(png|jpe?g);base64,)(.*)$#i', $dataUrl, $matches)) {
            return $this->invalid(
                'signature',
                'signature_invalid_format',
                'Signature must be a valid PNG or JPEG data URL.',
                null,
                ['expected_prefixes' => ['data:image/png;base64,', 'data:image/jpeg;base64,']]
            );
        }

        $mimeType = strtolower((string) ($matches[2] ?? 'png'));
        if ($mimeType === 'jpg') {
            $mimeType = 'jpeg';
        }
        $fileExtension = $mimeType === 'jpeg' ? 'jpg' : 'png';
        $encoded = (string) ($matches[3] ?? '');
        $decoded = base64_decode($encoded, true);

        if ($decoded === false || $decoded === '') {
            return $this->invalid('signature', 'signature_decode_failed', 'Signature could not be decoded.');
        }

        if (strlen($decoded) < 16) {
            return $this->invalid('signature', 'signature_too_small', 'Signature image is too small to be valid.');
        }

        if ($mimeType === 'png' && !str_starts_with($decoded, "\x89PNG\r\n\x1a\n")) {
            return $this->invalid('signature', 'signature_not_png', 'Signature must decode to a PNG image.');
        }

        if ($mimeType === 'jpeg' && !str_starts_with($decoded, "\xFF\xD8\xFF")) {
            return $this->invalid('signature', 'signature_not_jpeg', 'Signature must decode to a JPEG image.');
        }

        if (!function_exists('getimagesizefromstring')) {
            return $this->invalid('signature', 'signature_validator_unavailable', 'Signature image validation is unavailable in this environment.');
        }

        $imageInfo = @\getimagesizefromstring($decoded);
        if ($imageInfo === false || ($imageInfo[0] ?? 0) <= 0 || ($imageInfo[1] ?? 0) <= 0) {
            return $this->invalid('signature', 'signature_corrupted', 'Signature image is corrupted or unreadable.');
        }

        return ValidationResult::success([
            'signature_data_url' => $dataUrl,
            'signature_bytes' => $decoded,
            'signature_mime_type' => $mimeType === 'jpeg' ? 'image/jpeg' : 'image/png',
            'signature_file_extension' => $fileExtension,
            'signature_width' => (int) $imageInfo[0],
            'signature_height' => (int) $imageInfo[1],
        ]);
    }
}
