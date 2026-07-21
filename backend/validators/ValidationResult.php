<?php

declare(strict_types=1);

namespace Cidb\Backend\Validators;

final class ValidationResult
{
    /**
     * @param array<string, array<int, array{code: string, message: string, value?: mixed, meta?: array<string, mixed>}>> $errors
     * @param array<string, mixed> $data
     */
    private function __construct(
        private bool $valid,
        private array $errors = [],
        private array $data = []
    ) {
    }

    public static function success(array $data = []): self
    {
        return new self(true, [], $data);
    }

    public static function failure(array $errors = [], array $data = []): self
    {
        return new self(false, $errors, $data);
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    /**
     * @return array<string, array<int, array{code: string, message: string, value?: mixed, meta?: array<string, mixed>}>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    public function addError(string $field, string $code, string $message, mixed $value = null, array $meta = []): self
    {
        $this->valid = false;

        $error = [
            'code' => $code,
            'message' => $message,
        ];

        if ($value !== null) {
            $error['value'] = $value;
        }

        if ($meta !== []) {
            $error['meta'] = $meta;
        }

        $this->errors[$field][] = $error;

        return $this;
    }

    public function merge(self $other): self
    {
        if ($other->hasErrors()) {
            $this->valid = false;
            foreach ($other->errors() as $field => $errors) {
                foreach ($errors as $error) {
                    $this->errors[$field][] = $error;
                }
            }
        }

        if ($other->data() !== []) {
            $this->data = array_replace($this->data, $other->data());
        }

        return $this;
    }

    public function withData(array $data): self
    {
        $this->data = array_replace($this->data, $data);

        return $this;
    }

    /**
     * @return array{valid: bool, data: array<string, mixed>, errors: array<string, array<int, array{code: string, message: string, value?: mixed, meta?: array<string, mixed>}>>}
     */
    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'data' => $this->data,
            'errors' => $this->errors,
        ];
    }
}

