<?php

namespace Civi\OAuth;

use Civi;
use Civi\Core\Service\AutoService;
use CRM_OAuth_ExtensionUtil as E;
use CRM_Utils_Cache_Interface;
use GuzzleHttp\Client;

/**
 * Manage a connection to the `connect.civicrm.org` bridge-server.
 */
class CiviConnect extends AutoService {

  protected ?CRM_Utils_Cache_Interface $cache;

  /**
   * @service oauth_client.civi_connect
   * @inject crypto.registry, cache.long
   * @return \Civi\OAuth\CiviConnect
   */
  public static function factory(\Civi\Crypto\CryptoRegistry $registry, CRM_Utils_Cache_Interface $cache = NULL) {
    $instance = new static();
    $instance->cache = $cache;
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
   * Get a list of available bridge servers.
   *
   * @return array
   */
  public function getHosts(): array {
    $urlText = \Civi::settings()->get('oauth_civi_connect_urls');
    $urls = [];
    foreach (preg_split(';[ \r\n\t]+;', trim($urlText)) as $urlLine) {
      [$name, $url] = explode('=', $urlLine, 2);
      $urls[$name] = rtrim($url, '/');
    }

    $hosts = [];
    $hosts['live'] = [
      'url' => $urls['live'] ?? NULL,
      'name()' => fn($name) => $name,
      'title()' => fn($title) => $title,
      'tag' => 'CiviConnect',
    ];
    $hosts['sandbox'] = [
      'url' => $urls['sandbox'] ?? NULL,
      'name()' => fn($name) => $name . '_sandbox',
      'title()' => fn($title) => E::ts('%1 (Sandbox)', [1 => $title]),
      'tag' => 'CiviConnectSandbox',
    ];
    $hosts['local'] = [
      'url' => $urls['local'] ?? NULL,
      'name()' => fn($name) => $name . '_local',
      'title()' => fn($title) => E::ts('%1 (Local)', [1 => $title]),
      'tag' => 'CiviConnectLocal',
    ];

    return $hosts;
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

  /**
   * @param string $serviceUrl
   *   Ex: 'https://connect.civicrm.org/
   * @param string|null $redirectUri
   *   Ex: 'https://savewahles.org/civicrm/oauth-client/return'
   *
   * @return void
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function register(string $serviceUrl, ?string $redirectUri = NULL): void {
    $redirectUri ??= \CRM_OAuth_BAO_OAuthClient::getRedirectUri();
    $cacheKey = 'oauth_check_' . md5($serviceUrl . ' ' . $this->getId() . $redirectUri);
    try {
      (new Client())->post("$serviceUrl/account/redirect-url", [
        'form_params' => [
          'client_id' => $this->getId(),
          'client_secret' => $this->createAuthToken(),
          'redirect_uri' => $redirectUri,
        ],
      ]);
    }
    finally {
      $this->cache->delete($cacheKey);
    }
  }

  /**
   * @param string $authorizeUrl
   *   Ex: 'https://connect.civicrm.org/foobar/authorize
   * @param string|null $redirectUri
   *   Ex: 'https://savewahles.org/civicrm/oauth-client/return'
   *
   * @return void
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function isRegistered(string $authorizeUrl, ?string $redirectUri = NULL): bool {
    $redirectUri ??= \CRM_OAuth_BAO_OAuthClient::getRedirectUri();

    $serviceUrl = \CRM_Utils_Url::toOrigin($authorizeUrl);
    $cacheKey = 'oauth_check_' . md5($serviceUrl . ' ' . $this->getId() . $redirectUri);
    $registered = $this->cache->get($cacheKey);
    if ($registered !== NULL) {
      return $registered;
    }

    $response = (new Client())->post("$serviceUrl/account/check-url", [
      'http_errors' => FALSE,
      'form_params' => [
        'client_id' => $this->getId(),
        'client_secret' => $this->createAuthToken(),
        'redirect_uri' => $redirectUri,
      ],
    ]);
    $registered = ($response->getStatusCode() === 200);
    $codeType = floor($response->getStatusCode() / 100);
    if ($codeType === 2 || $codeType === 4) {
      $this->cache->set($cacheKey, $registered, 5 * 60);
    }
    return $registered;
  }

}
