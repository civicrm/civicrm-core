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

namespace Civi\Authx;

use Civi\Standalone\Security;

class Standalone implements AuthxInterface {

  /**
   * @inheritDoc
   */
  public function checkPassword(string $username, string $password) {
    return Security::singleton()->checkPassword($username, $password);
  }

  /**
   * @inheritDoc
   */
  public function loginSession($userId) {
    $this->loginStateless($userId);

    $session = \CRM_Core_Session::singleton();
    $session->set('ufID', $userId);

    // Identify the contact
    $user = \Civi\Api4\User::get(FALSE)
      ->addWhere('id', '=', $userId)
      ->execute()
      ->single();

    // Confusingly, Civi stores it's *Contact* ID as *userID* on the session.
    $session->set('userID', $user['contact_id'] ?? NULL);

    if (!empty($user['language'])) {
      $session->set('lcMessages', $user['language']);
    }
  }

  /**
   * @inheritDoc
   */
  public function logoutSession() {
    global $loggedInUserId;
    $loggedInUserId = NULL;
    \CRM_Core_Session::singleton()->reset();
    // session_destroy();
  }

  /**
   * @inheritDoc
   */
  public function loginStateless($userId) {
    global $loggedInUserId;
    $loggedInUserId = $userId;
  }

  /**
   * @inheritDoc
   */
  public function getCurrentUserId() {
    global $loggedInUserId;
    if (empty($loggedInUserId) && session_status() === PHP_SESSION_ACTIVE) {
      $loggedInUserId = \CRM_Core_Session::singleton()->get('ufID');
    }
    return $loggedInUserId;
  }

  /**
   * @inheritDoc
   */
  public function getUserIsBlocked($userId) {
    // ToDo
    return FALSE;
  }

}
