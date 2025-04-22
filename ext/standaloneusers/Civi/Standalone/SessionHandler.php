<?php

namespace Civi\Standalone;

use CRM_Standaloneusers_BAO_Session as Session;
use DB;
use SessionHandlerInterface;
use SessionIdInterface;
use SessionUpdateTimestampHandlerInterface;

class SessionHandler implements SessionHandlerInterface, SessionIdInterface, SessionUpdateTimestampHandlerInterface {

  /**
   * @var bool
   */
  private $collectGarbage = FALSE;

  /**
   * @var string
   */
  private $data;

  /**
   * @var \DB_mysqli
   */
  private $db;

  /**
   * @var int
   */
  private $maxLifetime;

  /**
   * Closes the current session. This function is automatically executed when
   * closing the session, or explicitly via session_write_close()
   *
   * @return bool
   */
  public function close(): bool {
    $this->db->commit();
    $this->db->autoCommit(TRUE);

    if ($this->collectGarbage) {
      $expiration_date = date('Y-m-d H:i:s', strtotime("- {$this->maxLifetime} seconds"));
      Session::deleteExpired($this->db, $expiration_date);
    }

    $this->collectGarbage = FALSE;
    unset($this->data);

    return TRUE;
  }

  /**
   * Create a unique session ID
   *
   * @return string
   */
  public function create_sid(): string {
    return bin2hex(random_bytes(32));
  }

  /**
   * Destroys a session. Called by session_regenerate_id() (with $destroy = true),
   * session_destroy() and when session_decode() fails
   *
   * @param string $id
   * @return bool
   */
  public function destroy($id): bool {
    Session::destroy($this->db, $id);

    return TRUE;
  }

  /**
   * Cleans up expired sessions. Called by session_start(), based on
   * session.gc_divisor, session.gc_probability and session.gc_maxlifetime settings
   *
   * @param int $max_lifetime
   * @return int|false
   */
  public function gc($max_lifetime): int {
    $this->collectGarbage = TRUE;
    $this->maxLifetime = $max_lifetime;

    return 0;
  }

  /**
   * Re-initialize existing session, or creates a new one. Called when a session
   * starts or when session_start() is invoked
   *
   * @param string $path
   * @param string $name
   * @return bool
   */
  public function open($path, $name): bool {
    $this->db = \CRM_Utils_SQL::connect(\CRM_Core_Config::singleton()->dsn);
    $this->db->autoCommit(FALSE);

    return TRUE;
  }

  /**
   * Reads the session data from the session storage, and returns the results.
   * Called right after the session starts or when session_start() is called
   *
   * @param string $id
   * @return string|false
   */
  public function read($id): string {
    return $this->data ?? '';
  }

  /**
   * Updates the last modification timestamp of the session. This function is
   * automatically executed when a session is updated.
   *
   * @param string $id
   * @param string $data
   * @return bool
   */
  public function updateTimestamp($id, $data): bool {
    Session::write($this->db, $id);

    return TRUE;
  }

  /**
   * Validates a given session ID. A session ID is valid, if a session with that
   * ID already exists. This function is automatically executed when a session
   * is to be started, a session ID is supplied and session.use_strict_mode is enabled.
   *
   * @param string $id
   * @return bool
   */
  public function validateId($id): bool {
    $session = Session::read($this->db, $id);

    if (is_null($session)) {
      return FALSE;
    }

    $this->data = $session['data'];
    $maxLifetime = \Civi::settings()->get('standaloneusers_session_max_lifetime');

    return strtotime($session['last_accessed']) >= strtotime("-$maxLifetime minutes");
  }

  /**
   * Writes the session data to the session storage. Called by session_write_close(),
   * when session_register_shutdown() fails, or during a normal shutdown.
   *
   * @param string $id
   * @param string $data
   * @return bool
   */
  public function write($id, $data): bool {
    Session::write($this->db, $id, $data);

    return TRUE;
  }

}
