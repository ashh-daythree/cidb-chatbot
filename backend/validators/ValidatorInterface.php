<?php

declare(strict_types=1);

namespace Cidb\Backend\Validators;

interface ValidatorInterface
{
    public function validate(mixed $input): ValidationResult;
}

