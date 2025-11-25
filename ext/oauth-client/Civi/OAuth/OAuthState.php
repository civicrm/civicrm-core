<?php

namespace Civi\OAuth;

use Civi\Core\Service\AutoService;

/**
 * @service oauth2.state
 */
class OAuthState extends AutoService {

  const LEGACY_TTL = 3600;

  /**
   * Session IDs are cookie values, so... "Any US-ASCII character excluding control characters
   * (ASCII characters 0 up to 31 and ASCII character 127), Whitespace, double quotes, commas,
   * semicolons, and backslashes."
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Set-Cookie#attributes
   */
  const SESSION_ID_REGEX = '/^([\x21\x23-\x2B\x2D-\x3A\x3C-\x5B\x5D-\x7E]+)$/';

  /**
   * When beginning OAuth flow from CLI, set the state with `session=>SESSION_WILDCARD`
   * to allow the pageflow to continue in a browser with an unknown session ID.
   *
   * Ideal value is (1) serializable and (2) invalid as cookie-content and (3) recognizable.
   */
  const SESSION_WILDCARD = ',;"wildcard';

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
   *   - clientId (int), the OAuthClient.id which initiated this flow
   *   - landingUrl (string, optional), If we want to ultimately redirect back to another part of our web UI
   *   - storage (string), Where to store the resulting token. Ex: OAuthSysToken or OAuthContactToken
   *   - scopes (array), List of scopes being requested
   *   - tag (string, optional), The symbolic tag to apply to the new token
   *   - code_verifier (string, optional), An extra string that we will send to the token-endpoint to prove that we initiated the flow
   *   - grant_type (string, optional), The kind of flow that we are pursuing. Default: authorization_code
   * @param string|null $stateId
   *   If specified, use the given state ID.
   * @return string
   *   State token / identifier
   */
  public function store($state, ?string $stateId = NULL): string {
    $stateId ??= 'CC_' . \CRM_Utils_String::createRandom(29, \CRM_Utils_String::ALPHANUMERIC);

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
    if (!($state['session'] === static::SESSION_WILDCARD || hash_equals($state['session'], $this->getSessionId()))) {
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
      return static::SESSION_WILDCARD;
    }

    // In general, we want `state`s to only be valid within a particular session.
    $id = session_id();
    if (!preg_match(static::SESSION_ID_REGEX, $id)) {
      // We double-check that ID is well-formed (n.b. not WILDCARD). This would
      // be true in any sensible session-management design. However, we have a mix
      // of HTTPDs, PHP versions, and PHP frameworks (and a box of magical $_COOKIEs),
      // so... we do an extra guard against unusual/non-conformant cases.
      throw new OAuthException("OAuth: Invalid session ID");
    }
    return $id;
  }

}
