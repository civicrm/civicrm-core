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
    switch ($key['suite']) {
      // Asymmetric key-pairs...
      case 'jwt-eddsa-keypair':
        $privateKey = base64_encode(sodium_crypto_sign_secretkey($key['key']));
        return JWT::encode($payload, $privateKey, 'EdDSA', $key['id']);

      case 'jwt-eddsa-public':
        throw new CryptoException("Cannot use public-key to sign JWT.");

      // Symmetric keys...
      default:
        $alg = $this->suiteToAlg($key['suite']);
        return JWT::encode($payload, $key['key'], $alg, $key['id']);
    }
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
    $keyRows = $this->getRegistry()->findKeysByTag($keyTag);
    if (empty($keyRows)) {
      throw new CryptoException("Unknown key/tag ($keyTag)");
    }

    $jwtKeys = [];
    foreach ($keyRows as $key) {
      switch ($key['suite']) {
        // Asymmetric key-pairs...
        case 'jwt-eddsa-keypair':
          $publicKey = base64_encode(sodium_crypto_sign_publickey($key['key']));
          $jwtKeys[$key['id']] = new Key($publicKey, 'EdDSA');
          break;

        case 'jwt-eddsa-public':
          $jwtKeys[$key['id']] = new Key(base64_encode($key['key']), 'EdDSA');
          break;

        // Symmetric keys...
        default:
          $alg = $this->suiteToAlg($key['suite']);
          $jwtKeys[$key['id']] = new Key($key['key'], $alg);
          break;
      }
    }

    try {
      return (array) JWT::decode($token, $jwtKeys);
    }
    catch (\UnexpectedValueException | \LogicException $e) {
      // Convert to satisfy `@throws CryptoException` and historical messaging.
      if (
        !preg_match(';unable to lookup correct key;', $e->getMessage())
        &&
        !preg_match(';Signature verification failed;', $e->getMessage())
      ) {
        throw new CryptoException(get_class($e) . ': ' . $e->getMessage(), 0, [], $e);
      }
      else {
        throw new CryptoException('Signature verification failed', 0, [], $e);
      }
    }
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
