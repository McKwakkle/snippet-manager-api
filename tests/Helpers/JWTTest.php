<?php

namespace Tests\Helpers;

use App\Helpers\JWT;
use PHPUnit\Framework\TestCase;

class JWTTest extends TestCase
{

    public function test_encode_returns_a_string(): void
    {
        $token = JWT::encode(['user_id' => 'abc123']);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function test_decoded_token_contains_original_payload(): void
    {
        $token   = JWT::encode(['user_id' => 'abc123']);
        $decoded = JWT::decode($token);

        $this->assertEquals('abc123', $decoded->user_id);
    }

    public function test_decoded_token_contains_iat_and_exp_claims(): void
    {
        $token   = JWT::encode(['user_id' => 'abc123']);
        $decoded = JWT::decode($token);

        $this->assertObjectHasProperty('iat', $decoded);
        $this->assertObjectHasProperty('exp', $decoded);
    }

    public function test_exp_is_24_hours_after_iat(): void
    {
        $token   = JWT::encode(['user_id' => 'abc123']);
        $decoded = JWT::decode($token);

        $this->assertEquals(60 * 60 * 24, $decoded->exp - $decoded->iat);
    }

    public function test_decode_throws_on_invalid_token(): void
    {
        $this->expectException(\Exception::class);

        JWT::decode('this.is.not.a.valid.token');
    }

    public function test_decode_throws_on_tampered_token(): void
    {
        $token  = JWT::encode(['user_id' => 'abc123']);
        $parts  = explode('.', $token);
        $parts[1] = base64_encode('{"user_id":"hacker"}');
        $tampered = implode('.', $parts);

        $this->expectException(\Exception::class);

        JWT::decode($tampered);
    }

    public function test_get_token_from_header_returns_null_when_no_header(): void
    {
        $token = JWT::getTokenFromHeader();

        $this->assertNull($token);
    }
}