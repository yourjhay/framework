<?php

namespace Simple\Validation;

use Simple\Request;

abstract class FormRequest extends Request
{
    private array $validatedData = [];
    private bool $validated = false;

    abstract public function rules(): array;

    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [];
    }

    public function fields(): array
    {
        return [];
    }

    public function validate(): void
    {

        if ($this->validated) {
            return;
        }

        if (!$this->authorize()) {
            throw new \RuntimeException('Unauthorized action.');
        }

        $v = new Validator;
        $v->validation_rules($this->rules());

        if ($messages = $this->messages()) {
            $v->set_fields_error_messages($messages);
        }

        if ($fields = $this->fields()) {
            $v->set_field_names($fields);
        }
        $data = array_merge(
            $this->query->all(),
            $this->request->all(),
            $this->files->all()
        );

        $result = $v->run($data);

        if ($result === false) {
            throw new ValidationException($v->get_errors_array());
        }

        $this->validatedData = $result;
        $this->validated = true;
    }

    public function validated(): array
    {
        if (!$this->validated) {
            $this->validate();
        }

        return $this->validatedData;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        if (!$this->validated) {
            $this->validate();
        }

        return $this->validatedData[$key] ?? $default;
    }
}
