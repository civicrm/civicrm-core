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

class None implements AuthxInterface {

  /**
   * @inheritDoc
   */
  public function checkPassword(string $username, string $password) {
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function loginSession($userId) {
    throw new \Exception("Cannot login: Unrecognized user framework");
  }

  /**
   * @inheritDoc
   */
  public function logoutSession() {
    throw new \Exception("Cannot logout: Unrecognized user framework");
  }

  /**
   * @inheritDoc
   */
  public function loginStateless($userId) {
    throw new \Exception("Cannot login: Unrecognized user framework");
  }

  /**
   * @inheritDoc
   */
  public function getCurrentUserId() {
    throw new \Exception("Cannot determine active user: Unrecognized user framework");
  }

  /**
   * @inheritDoc
   */
  public function getUserIsBlocked($userId) {
    // ToDo
    return FALSE;
  }

}
