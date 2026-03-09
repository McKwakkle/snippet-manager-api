<?php
namespace App\Helpers;

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;
// Will maybe remove these at a later stage, decided to not use them
// but things might change depending on the how the project evolves
//use Firebase\JWT\ExpiredException;
//use Firebase\JWT\SignatureInvalidException;

class JWT
{
  private static string $algorithm = 'HS256';
  public static function encode(array $payload): string
  {
    $secret = $_ENV['JWT_SECRET'] ?? '';

    $payload['iat'] = time();
    $payload['exp'] = time() + (60 * 60 * 24);

    return FirebaseJWT::encode($payload, $secret, self::$algorithm);
  }

  public static function decode(string $token): object
  {
    $secret = $_ENV['JWT_SECRET'] ?? '';
    return FirebaseJWT::decode($token, new Key($secret, self::$algorithm));
  }
  public static function getTokenFromHeader(): ?string
{
    $headers = function_exists('getallheaders') ? \getallheaders() : [];
    $auth    = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (empty($auth)) {
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    }

    if (str_starts_with($auth, 'Bearer ')) {
        return substr($auth, 7);
    }

    return null;
}
}
