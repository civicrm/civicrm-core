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
    return $security->checkPassword($password, $user['password'] ?? '') ? $user['id'] : NULL;
  }

  /**
   * @inheritDoc
   */
  public function loginSession($userId) {
    $this->loginStateless($userId);

    $session = \CRM_Core_Session::singleton();
    $session->set('ufId', $userId);

    // Identify the contact
    $contactID = civicrm_api3('UFMatch', 'get', [
      'sequential' => 1,
      'return' => ['contact_id'],
      'uf_id' => $userId
    ])['values'][0]['contact_id'] ?? NULL;
    // Confusingly, Civi stores it's *Contact* ID as *userId* on the session.
    $session->set('userId', $contactID);
  }

  /**
   * @inheritDoc
   */
  public function logoutSession() {
    \CRM_Core_Session::singleton()->reset();
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
      $loggedInUserId = \CRM_Core_Session::singleton()->get('ufId');
    }
    return $loggedInUserId;
  }

}
