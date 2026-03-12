<?php

namespace Tests\Helpers;

use App\Helpers\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{

  public function test_required_passes_when_field_present(): void
  {
    $validator = (new Validator(['name' => 'Kellan']))->required('name');

    $this->assertFalse($validator->fails());
  }

  public function test_required_fails_when_field_missing(): void
  {
    $validator = (new Validator([]))->required('name');

    $this->assertTrue($validator->fails());
    $this->assertArrayHasKey('name', $validator->errors());
  }

  public function test_required_fails_when_field_is_empty_string(): void
  {
    $validator = (new Validator(['name' => '   ']))->required('name');

    $this->assertTrue($validator->fails());
  }


  public function test_email_passes_with_valid_email(): void
  {
    $validator = (new Validator(['email' => 'kellan@test.com']))->email('email');

    $this->assertFalse($validator->fails());
  }

  public function test_email_fails_with_invalid_email(): void
  {
    $validator = (new Validator(['email' => 'not-an-email']))->email('email');

    $this->assertTrue($validator->fails());
    $this->assertArrayHasKey('email', $validator->errors());
  }

  public function test_email_skips_validation_when_field_absent(): void
  {
    $validator = (new Validator([]))->email('email');

    $this->assertFalse($validator->fails());
  }

  public function test_min_passes_when_value_long_enough(): void
  {
    $validator = (new Validator(['password' => 'secret123']))->min('password', 8);

    $this->assertFalse($validator->fails());
  }

  public function test_min_fails_when_value_too_short(): void
  {
    $validator = (new Validator(['password' => 'short']))->min('password', 8);

    $this->assertTrue($validator->fails());
    $this->assertArrayHasKey('password', $validator->errors());
  }


  public function test_max_passes_when_value_short_enough(): void
  {
    $validator = (new Validator(['username' => 'kellan']))->max('username', 50);

    $this->assertFalse($validator->fails());
  }

  public function test_max_fails_when_value_too_long(): void
  {
    $validator = (new Validator(['username' => str_repeat('a', 51)]))->max('username', 50);

    $this->assertTrue($validator->fails());
    $this->assertArrayHasKey('username', $validator->errors());
  }


  public function test_matches_passes_when_fields_are_equal(): void
  {
    $validator = (new Validator([
      'password' => 'secret123',
      'password_confirmation' => 'secret123',
    ]))->matches('password', 'password_confirmation');

    $this->assertFalse($validator->fails());
  }

  public function test_matches_fails_when_fields_differ(): void
  {
    $validator = (new Validator([
      'password' => 'secret123',
      'password_confirmation' => 'different',
    ]))->matches('password', 'password_confirmation');

    $this->assertTrue($validator->fails());
    $this->assertArrayHasKey('password', $validator->errors());
  }


  public function test_in_passes_when_value_is_allowed(): void
  {
    $validator = (new Validator(['visibility' => 'public']))->in('visibility', ['public', 'private']);

    $this->assertFalse($validator->fails());
  }

  public function test_in_fails_when_value_not_allowed(): void
  {
    $validator = (new Validator(['visibility' => 'secret']))->in('visibility', ['public', 'private']);

    $this->assertTrue($validator->fails());
    $this->assertArrayHasKey('visibility', $validator->errors());
  }


  public function test_integer_passes_with_valid_integer(): void
  {
    $validator = (new Validator(['intensity' => '10']))->integer('intensity');

    $this->assertFalse($validator->fails());
  }

  public function test_integer_fails_with_non_integer(): void
  {
    $validator = (new Validator(['intensity' => 'abc']))->integer('intensity');

    $this->assertTrue($validator->fails());
    $this->assertArrayHasKey('intensity', $validator->errors());
  }


  public function test_range_passes_when_value_within_range(): void
  {
    $validator = (new Validator(['intensity' => '10']))->range('intensity', 1, 20);

    $this->assertFalse($validator->fails());
  }

  public function test_range_fails_when_value_outside_range(): void
  {
    $validator = (new Validator(['intensity' => '25']))->range('intensity', 1, 20);

    $this->assertTrue($validator->fails());
    $this->assertArrayHasKey('intensity', $validator->errors());
  }


  public function test_multiple_rules_can_be_chained(): void
  {
    $validator = (new Validator([
      'email' => 'kellan@test.com',
      'password' => 'secret123',
    ]))
      ->required('email')
      ->email('email')
      ->required('password')
      ->min('password', 8);

    $this->assertFalse($validator->fails());
  }

  public function test_multiple_errors_are_collected(): void
  {
    $validator = (new Validator([]))
      ->required('email')
      ->required('password');

    $this->assertTrue($validator->fails());
    $this->assertCount(2, $validator->errors());
  }


  public function test_validated_returns_only_passing_fields(): void
  {
    $validator = (new Validator([
      'email' => 'not-an-email',
      'username' => 'kellan',
    ]))
      ->email('email')
      ->required('username');

    $validated = $validator->validated();

    $this->assertArrayNotHasKey('email', $validated);
    $this->assertArrayHasKey('username', $validated);
  }

  public function test_password_passes_with_valid_password(): void
  {
    $validator = (new Validator(['password' => 'Secret1!']))->password('password');

    $this->assertFalse($validator->fails());
  }

  public function test_password_fails_when_too_short(): void
  {
    $validator = (new Validator(['password' => 'Sh0rt!']))->password('password');

    $this->assertTrue($validator->fails());
    $this->assertArrayHasKey('password', $validator->errors());
  }

  public function test_password_fails_without_uppercase(): void
  {
    $validator = (new Validator(['password' => 'secret1!']))->password('password');

    $this->assertTrue($validator->fails());
    $this->assertArrayHasKey('password', $validator->errors());
  }

  public function test_password_fails_without_number(): void
  {
    $validator = (new Validator(['password' => 'Secret!!']))->password('password');

    $this->assertTrue($validator->fails());
    $this->assertArrayHasKey('password', $validator->errors());
  }

  public function test_password_fails_without_symbol(): void
  {
    $validator = (new Validator(['password' => 'Secret123']))->password('password');

    $this->assertTrue($validator->fails());
    $this->assertArrayHasKey('password', $validator->errors());
  }

  public function test_password_skips_validation_when_field_absent(): void
  {
    $validator = (new Validator([]))->password('password');

    $this->assertFalse($validator->fails());
  }

  public function test_password_collects_multiple_failures(): void
  {
    $validator = (new Validator(['password' => 'weak']))->password('password');

    $this->assertTrue($validator->fails());
    $this->assertArrayHasKey('password', $validator->errors());
  }
}