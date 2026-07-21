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

        if (!str_starts_with($dataUrl, 'data:image/png;base64,')) {
            return $this->invalid(
                'signature',
                'signature_invalid_format',
                'Signature must be a valid PNG data URL.',
                null,
                ['expected_prefix' => 'data:image/png;base64,']
            );
        }

        $encoded = substr($dataUrl, strlen('data:image/png;base64,'));
        $decoded = base64_decode($encoded, true);

        if ($decoded === false || $decoded === '') {
            return $this->invalid('signature', 'signature_decode_failed', 'Signature could not be decoded.');
        }

        if (strlen($decoded) < 16) {
            return $this->invalid('signature', 'signature_too_small', 'Signature image is too small to be valid.');
        }

        if (!str_starts_with($decoded, "\x89PNG\r\n\x1a\n")) {
            return $this->invalid('signature', 'signature_not_png', 'Signature must decode to a PNG image.');
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
            'signature_width' => (int) $imageInfo[0],
            'signature_height' => (int) $imageInfo[1],
        ]);
    }
}
