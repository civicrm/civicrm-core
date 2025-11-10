<?php

namespace Civi\OAuth;

use Civi\Core\Service\AutoService;

/**
 * @service oauth2.state
 */
class OAuthState extends AutoService {

  const LEGACY_TTL = 3600;

  /**
   * @var \CRM_Utils_Cache_Interface
   * @inject cache.session
   */
  protected $cache;

  /**
   * @param array $state
   *   Flexible data. Standard keys:
   *   - session (string), automatically defined
   *   - time (int), creation time; seconds since epoch. Default: NOW
   *   - ttl (int), the number of seconds for which this record is valid. Default: LEGACY_TTL
   * @return string
   *   State token / identifier
   */
  public function store($state): string {
    $stateId = 'CC_' . \CRM_Utils_String::createRandom(29, \CRM_Utils_String::ALPHANUMERIC);

    $state['session'] = $this->getSessionId();
    $state['time'] = \CRM_Utils_Time::time();
    $ttl = $state['ttl'] ?? self::LEGACY_TTL;

    // We store this in cache layer to ensure that stale records will be cleaned up automatically.
    $this->cache->set($stateId, $state, $ttl);

    return $stateId;
  }

  /**
   * Restore from the $stateId.
   *
   * @param string $stateId
   * @return mixed
   * @throws \Civi\OAuth\OAuthException
   */
  public function load($stateId) {
    [$type, $id] = explode('_', $stateId);
    $state = match($type) {
      'CC' => $this->cache->get($stateId),
      default => NULL,
    };

    if (!$state) {
      throw new \Civi\OAuth\OAuthException("OAuth: Received invalid or expired state");
    }
    if (!($state['session'] === '*' || hash_equals($state['session'], $this->getSessionId()))) {
      throw new \Civi\OAuth\OAuthException("OAuth: Received invalid or expired state");
    }
    $ttl = $state['ttl'] ?? self::LEGACY_TTL;
    if (!isset($state['time']) || $state['time'] + $ttl < \CRM_Utils_Time::time()) {
      throw new \Civi\OAuth\OAuthException("OAuth: Received invalid or expired state");
    }

    return $state;
  }

  protected function getSessionId(): string {
    if (PHP_SAPI === 'cli') {
      // CLI doesn't have a session. If we generate OAuthState on CLI, then allow it to be used in any browser session.
      return '*';
    }

    // In general, we want `state`s to only be valid within a particular session.
    return session_id();
  }

}
