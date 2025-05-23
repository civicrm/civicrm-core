<?php

class CRM_Standaloneusers_BAO_Session extends CRM_Standaloneusers_DAO_Session {

  /**
   * Delete all expired sessions
   *
   * @param DB_mysqli $db
   * @param string $expiration_date
   * @return void
   */
  public static function deleteExpired($db, $expiration_date) {
    $table_name = self::getTableName();
    $stmt = $db->prepare("DELETE FROM $table_name WHERE last_accessed < ?");
    $db->execute($stmt, $expiration_date);
  }

  /**
   * Delete a session with a specific session ID
   *
   * @param DB_mysqli $db
   * @param string $session_id
   * @return void
   */
  public static function destroy($db, $session_id) {
    $table_name = self::getTableName();
    $stmt = $db->prepare("DELETE FROM $table_name WHERE session_id = ?");
    $db->execute($stmt, $session_id);
  }

  /**
   * Read serialized session data
   *
   * @param DB_mysqli $db
   * @param string $session_id
   * @return string
   */
  public static function read($db, $session_id) {
    $table_name = self::getTableName();
    $stmt = $db->prepare("SELECT * FROM $table_name WHERE session_id = ? FOR UPDATE");

    return $db->execute($stmt, $session_id)->fetchRow(DB_FETCHMODE_ASSOC);
  }

  /**
   * Update session data or just the last_accessed timestamp if no data is provided
   *
   * @param DB_mysqli $db
   * @param string $session_id
   * @param string $data
   * @return void
   */
  public static function write($db, $session_id, $data = NULL) {
    $table_name = self::getTableName();

    if (is_null($data)) {
      $stmt = $db->prepare("
        INSERT INTO $table_name (session_id, last_accessed) VALUES (?, NOW())
        ON DUPLICATE KEY UPDATE last_accessed = NOW()
      ");

      $db->execute($stmt, $session_id);
    }
    else {
      $stmt = $db->prepare("
        INSERT INTO $table_name (session_id, data, last_accessed) VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE data = ?, last_accessed = NOW()
      ");

      $db->execute($stmt, [$session_id, $data, $data]);
    }
  }

}
