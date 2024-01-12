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
    $security = Security::singleton();
    $user = $security->loadUserByName($username);
    return $security->checkPassword($password, $user['hashed_password'] ?? '') ? $user['id'] : NULL;
  }

  /**
   * @inheritDoc
   */
  public function loginSession($userId) {
    $user = Security::singleton()->loadUserByID($userId);
    Security::singleton()->loginAuthenticatedUserRecord($user, TRUE);
  }

  /**
   * @inheritDoc
   */
  public function logoutSession() {
    \CRM_Core_Session::singleton()->reset();
    session_destroy();
  }

  /**
   * @inheritDoc
   */
  public function loginStateless($userId) {
    $user = Security::singleton()->loadUserByID($userId);
    Security::singleton()->loginAuthenticatedUserRecord($user, FALSE);
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

}
