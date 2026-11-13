<?php

namespace Civi\OAuth;

/**
 * Discovery fields needed for authorization code flow with signed ID tokens.
 */
final class OpenIdMetadata {

  public static function httpsUrl(string $url): void {
    $parts = parse_url($url);
    if (!$parts || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])
      || !filter_var($url, FILTER_VALIDATE_URL) || str_contains($url, '\\')
      || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])) {
      throw new \InvalidArgumentException('Invalid HTTPS endpoint.');
    }
  }

  public static function fromArray(array $document, string $expectedIssuer): array {
    if (($document['issuer'] ?? NULL) !== $expectedIssuer) {
      throw new \InvalidArgumentException('OpenID issuer does not match the configured issuer.');
    }
    $metadata = [];
    foreach (['issuer', 'authorization_endpoint', 'token_endpoint', 'jwks_uri'] as $field) {
      $url = $document[$field] ?? NULL;
      if (!is_string($url) || ($field === 'issuer' && parse_url($url, PHP_URL_QUERY) !== NULL)) {
        throw new \InvalidArgumentException("Invalid OpenID metadata field: $field");
      }
      self::httpsUrl($url);
      $metadata[$field] = $url;
    }
    return $metadata;
  }

}
