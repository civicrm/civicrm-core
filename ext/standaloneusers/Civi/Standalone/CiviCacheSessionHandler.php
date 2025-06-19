<?php

namespace Civi\Standalone;

use SessionHandlerInterface;
use SessionIdInterface;
use SessionUpdateTimestampHandlerInterface;

/**
 * Custom session handler using CiviCRM's session cache.
 */
class CiviCacheSessionHandler implements SessionHandlerInterface, SessionIdInterface, SessionUpdateTimestampHandlerInterface {

  /**
   * The session cache instance.
   *
   * @var \CRM_Utils_Cache_Interface
   */
  private $cache;

  /**
   * Maximum session lifetime (in seconds).
   *
   * @var int
   */
  private $maxLifetime;

  /**
   * Constructs a new session handler.
   *
   * @param int $maxLifetime
   *   The session max lifetime.
   */
  public function __construct(int $maxLifetime = 1440) {
    $this->cache = \Civi::cache('session');
    $this->maxLifetime = $maxLifetime;
  }

  /**
   * Re-initialize existing session or create a new one.
   *
   * @param string $path
   * @param string $name
   *
   * @return bool
   */
  public function open($path, $name): bool {
    // No action needed; cache backend is already initialized.
    return TRUE;
  }

  /**
   * Closes the current session.
   *
   * @return bool
   */
  public function close(): bool {
    return TRUE;
  }

  /**
   * Reads the session data.
   *
   * @param string $id
   *   The session ID.
   *
   * @return string
   */
  public function read($id): string {
    $data = $this->cache->get($id);
    return $data !== NULL ? $data : '';
  }

  /**
   * Writes the session data.
   *
   * @param string $id
   *   The session ID.
   * @param string $data
   *   The session data.
   *
   * @return bool
   */
  public function write($id, $data): bool {
    $this->cache->set($id, $data, $this->maxLifetime);
    return TRUE;
  }

  /**
   * Destroys a session.
   *
   * @param string $id
   *   The session ID.
   *
   * @return bool
   */
  public function destroy($id): bool {
    $this->cache->delete($id);
    return TRUE;
  }

  /**
   * Cleans up expired sessions.
   *
   * @param int $max_lifetime
   *   The max session lifetime.
   *
   * @return int|false
   */
  public function gc($max_lifetime): int|FALSE {
    // Cache backend handles garbage collection automatically.
    return 0;
  }

  /**
   * Creates a unique session ID.
   *
   * @return string
   */
  public function create_sid(): string {
    return bin2hex(random_bytes(32));
  }

  /**
   * Validates a session ID.
   *
   * @param string $id
   *   The session ID.
   *
   * @return bool
   */
  public function validateId($id): bool {
    return $this->cache->get($id) !== NULL;
  }

  /**
   * Updates the last modification timestamp of the session.
   *
   * @param string $id
   *   The session ID.
   * @param string $data
   *   The session data.
   *
   * @return bool
   */
  public function updateTimestamp($id, $data): bool {
    $current = $this->cache->get($id);
    if ($current !== NULL) {
      // Refresh TTL by re-setting the existing data.
      $this->cache->set($id, $current, $this->maxLifetime);
    }
    return TRUE;
  }

}
