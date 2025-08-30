<?php

namespace Civi\OAuth;

use Civi;
use Civi\Core\Service\AutoService;

/**
 * Manage a connection to the `connect.civicrm.org` bridge-server.
 */
class CiviConnect extends AutoService {

  /**
   * @service oauth_client.civi_connect
   * @inject crypto.registry
   * @return \Civi\OAuth\CiviConnect
   */
  public static function factory(\Civi\Crypto\CryptoRegistry $registry) {
    $instance = new static();
    // Registering our key via factory() means that we guarantee CRED key is already registered,
    // which helps with parsing. If using the factory is a problem, then CONNECT key probably
    // needs async registration, eg `$registry->addKey(['callback' => ...])`.
    if (!empty(Civi::settings()->get('oauth_civi_connect_keypair'))) {
      $registry->addKey($instance->createRegistration());
    }
    else {
      $instance->generateCreds();
    }
    return $instance;
  }

  /**
   * Find or create the connection parameters for CiviConnect bridge service.
   *
   * @return array
   *   Tuple: [clientId, clientSecret]
   */
  public function getCreds(): array {
    return [$this->getId(), $this->createAuthToken()];
  }

  public function getId(): ?string {
    $registry = Civi::service('crypto.registry');
    foreach ($registry->findKeysByTag('CONNECT') as $key) {
      return $key['id'];
    }
    return NULL;
  }

  /**
   * Generate a new key-pair to identify the current deployment.
   *
   * @return static
   */
  public function generateCreds(): CiviConnect {
    $keyPair = sodium_crypto_sign_keypair();
    Civi::settings()->set('oauth_civi_connect_keypair',
      Civi::service('crypto.token')->encrypt($keyPair, 'CRED')
    );
    Civi::service('crypto.registry')->addKey($this->createRegistration());
    return $this;
  }

  /**
   * Generate metadata/registration record for our key-pair.
   *
   * @return array
   * @see \Civi\Crypto\CryptoRegistry::addKey()
   */
  protected function createRegistration(): array {
    $encryptedKeyPair = Civi::settings()->get('oauth_civi_connect_keypair');
    $keyPair = Civi::service('crypto.token')->decrypt($encryptedKeyPair, 'CRED');
    return [
      'key' => $keyPair,
      'suite' => 'jwt-eddsa-keypair',
      'tags' => ['CONNECT'],
      'id' => $this->createId($keyPair),
    ];
  }

  public function createAuthToken(array $claims = []): string {
    return Civi::service('crypto.jwt')->encode(array_merge([
      'exp' => \CRM_Utils_Time::strtotime('+1 hour'),
      'scope' => 'CiviConnect',
    ], $claims), 'CONNECT');
  }

  private function createId(string $keyPair): string {
    return 'eddsa_' . base64_encode(sodium_crypto_sign_publickey($keyPair));
  }

}
