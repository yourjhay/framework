<?php

namespace Simple\Validation;

class ValidationException extends \RuntimeException
{
    private array $errors;

    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct('Validation failed.');
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
