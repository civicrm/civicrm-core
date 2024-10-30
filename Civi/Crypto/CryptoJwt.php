<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Crypto;

use Civi\Core\Service\AutoService;
use Civi\Crypto\Exception\CryptoException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * The "Crypto JWT" service supports a token format suitable for
 * exchanging/transmitting with external consumers (e.g. web-browsers).
 * It integrates with the CryptoRegistry (which is a source of valid signing keys).
 *
 * By default, tokens are signed and validated using any 'SIGN'ing keys
 * (ie 'CIVICRM_SIGN_KEYS').
 *
 * @package Civi\Crypto
 * @see https://jwt.io/
 * @service crypto.jwt
 */
class CryptoJwt extends AutoService {

  /**
   * @var \Civi\Crypto\CryptoRegistry
   */
  protected $registry;

  /**
   * @param array $payload
   *   List of JWT claims. See IANA link below.
   * @param string $keyIdOrTag
   *   Choose a valid key from the CryptoRegistry using $keyIdOrTag.
   * @return string
   * @throws \Civi\Crypto\Exception\CryptoException
   *
   * @see https://www.iana.org/assignments/jwt/jwt.xhtml
   */
  public function encode($payload, $keyIdOrTag = 'SIGN') {
    $key = $this->getRegistry()->findKey($keyIdOrTag);
    $alg = $this->suiteToAlg($key['suite']);
    // Currently, registry only has symmetric keys in $key['key']. For public key-pairs, might need to change.
    return JWT::encode($payload, $key['key'], $alg, $key['id']);
  }

  /**
   * @param string $token
   *   The JWT token.
   * @param string $keyTag
   *   Lookup valid keys from the CryptoRegistry using $keyTag.
   * @return array
   *   List of validated JWT claims.
   * @throws CryptoException
   */
  public function decode($token, $keyTag = 'SIGN') {
    // TODO: Circa mid-2024, make a hard-requirement on firebase/php-jwt v5.5+.
    // Then we can remove this guard and simplify the `$keysByAlg` stuff.
    $useKeyObj = class_exists(Key::class);
    if (!$useKeyObj) {
      \CRM_Core_Error::deprecatedWarning('Using deprecated version of firebase/php-jwt. Upgrade to 6.x+.');
    }
    $keyRows = $this->getRegistry()->findKeysByTag($keyTag);

    // We want to call JWT::decode(), but there's a slight mismatch -- the
    // registry contains whitelisted permutations of ($key,$alg), but
    // JWT::decode() accepts all permutations ($keys x $algs).

    // Grouping by alg will give proper granularity and also produces one
    // call to JWT::decode() in typical usage.

    // Defn: $keysByAlg[$alg][$keyId] === $keyData
    $keysByAlg = [];
    foreach ($keyRows as $key) {
      if ($alg = $this->suiteToAlg($key['suite'])) {
        // Currently, registry only has symmetric keys in $key['key']. For public key-pairs, might need to change.
        $keysByAlg[$alg][$key['id']] = ($useKeyObj ? new Key($key['key'], $alg) : $key['key']);
      }
    }

    foreach ($keysByAlg as $alg => $keys) {
      try {
        return ($useKeyObj ? (array) JWT::decode($token, $keys) : (array) JWT::decode($token, $keys, [$alg]));
      }
      catch (\UnexpectedValueException $e) {
        // Depending on the error, we might able to try other algos
        if (
          !preg_match(';unable to lookup correct key;', $e->getMessage())
          &&
          !preg_match(';Signature verification failed;', $e->getMessage())
        ) {
          // Keep our signature independent of the implementation.
          throw new CryptoException(get_class($e) . ': ' . $e->getMessage());
        }
      }
    }

    throw new CryptoException('Signature verification failed');
  }

  /**
   * @param string $suite
   *   Ex: 'jwt-hs256', 'jwt-hs384'
   * @return string
   *   Ex: 'HS256', 'HS384'
   */
  protected static function suiteToAlg($suite) {
    if (substr($suite, 0, 4) === 'jwt-') {
      return strtoupper(substr($suite, 4));
    }
    else {
      return NULL;
    }
  }

  /**
   * @return CryptoRegistry
   */
  protected function getRegistry(): CryptoRegistry {
    if ($this->registry === NULL) {
      $this->registry = \Civi::service('crypto.registry');
    }
    return $this->registry;
  }

}
