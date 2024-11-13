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

class Backdrop implements AuthxInterface {

  /**
   * @inheritDoc
   */
  public function checkPassword(string $username, string $password) {
    $uid = user_authenticate($username, $password);
    // Ensure strict nullness.
    return $uid ?: NULL;
  }

  /**
   * @inheritDoc
   */
  public function loginSession($userId) {
    global $user;
    $user = user_load($userId);
    user_login_finalize();
  }

  /**
   * @inheritDoc
   */
  public function logoutSession() {
    module_load_include('inc', 'user', 'user.pages');
    user_logout();
  }

  /**
   * @inheritDoc
   */
  public function loginStateless($userId) {
    backdrop_save_session(FALSE);
    global $user;
    $user = user_load($userId);
  }

  /**
   * @inheritDoc
   */
  public function getCurrentUserId() {
    global $user;
    return $user && $user->uid ? $user->uid : NULL;
  }

}
