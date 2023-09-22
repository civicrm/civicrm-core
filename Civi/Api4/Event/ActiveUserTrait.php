<?php

namespace Civi\Api4\Event;

trait ActiveUserTrait {

  /**
   * Contact ID of the active/target user (whose access we must check).
   * 0 for anonymous.
   *
   * @var int
   */
  private $userID;

  /**
   * @param int|null $userID
   *   Contact ID of the active/target user (whose access we must check).
   *   0 for anonymous.
   * @return $this
   */
  protected function setUser(int $userID) {
    $loggedInContactID = \CRM_Core_Session::getLoggedInContactID() ?: 0;
    if ($userID !== $loggedInContactID) {
      throw new \RuntimeException("The API subsystem does not yet fully support variable user IDs.");
      // Traditionally, the API events did not emit information about the current user; it was assumed
      // that the user was the logged-in user. This may be expanded in the future to support some more edge-cases.
      // For now, the semantics are unchanged - but we've begun reporting the active userID so that
      // consumers can start adopting it.
    }
    $this->userID = $userID;
    return $this;
  }

  /**
   * @return int
   *   Contact ID of the active/target user (whose access we must check).
   *   0 for anonymous.
   */
  public function getUserID(): int {
    return $this->userID;
  }

}
