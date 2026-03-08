<?php

namespace App\Helpers;

class Validator
{
  private array $errors = [];
  private array $data = [];

  public function __construct(array $data)
  {
    $this->data = $data;
  }

  public function required(string $field): self
  {
    if (!isset($this->data[$field]) || trim((string) $this->data[$field]) === '') {
      $this->errors[$field] = "{$field} is required";
    }

    return $this;
  }

  public function email(string $field): self
  {
    if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
      $this->errors[$field] = "{$field} must be a valid email address";
    }

    return $this;
  }

  public function min(string $field, int $min): self
  {
    if (isset($this->data[$field]) && strlen((string) $this->data[$field]) < $min) {
      $this->errors[$field] = "{$field} must be at least {$min} characters";
    }

    return $this;
  }

  public function max(string $field, int $max): self
  {
    if (isset($this->data[$field]) && strlen((string) $this->data[$field]) > $max) {
      $this->errors[$field] = "{$field} must not exceed {$max} characters";
    }

    return $this;
  }

  public function matches(string $field, string $otherField): self
  {
    if (
      isset($this->data[$field], $this->data[$otherField]) &&
      $this->data[$field] !== $this->data[$otherField]
    ) {
      $this->errors[$field] = "{$field} must match {$otherField}";
    }

    return $this;
  }

  public function in(string $field, array $allowed): self
  {
    if (isset($this->data[$field]) && !in_array($this->data[$field], $allowed, strict: true)) {
      $this->errors[$field] = "{$field} must be one of: " . implode(', ', $allowed);
    }

    return $this;
  }

  public function integer(string $field): self
  {
    if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_INT)) {
      $this->errors[$field] = "{$field} must be an integer";
    }

    return $this;
  }

  public function range(string $field, int $min, int $max): self
  {
    if (isset($this->data[$field])) {
      $value = (int) $this->data[$field];
      if ($value < $min || $value > $max) {
        $this->errors[$field] = "{$field} must be between {$min} and {$max}";
      }
    }

    return $this;
  }

  public function fails(): bool
  {
    return count($this->errors) > 0;
  }

  public function errors(): array
  {
    return $this->errors;
  }

  public function validated(): array
  {
    return array_filter(
      $this->data,
      fn($key) => !isset($this->errors[$key]),
      ARRAY_FILTER_USE_KEY
    );
  }
}
