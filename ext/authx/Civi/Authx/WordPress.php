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

class WordPress implements AuthxInterface {

  /**
   * @inheritDoc
   */
  public function checkPassword(string $username, string $password) {
    $user = wp_authenticate($username, $password);
    if (is_wp_error($user)) {
      return NULL;
    }
    return $user->ID;
  }

  /**
   * @inheritDoc
   */
  public function loginSession($userId) {
    // We use wp_signon() to try to fire any session-related events.
    // Note that we've already authenticated the user, so we filter 'authenticate'
    // to signal the chosen user.

    $user = get_user_by('id', $userId);
    $pickUser = function () use ($user) {
      return $user;
    };
    try {
      add_filter('authenticate', $pickUser);
      wp_signon();
      wp_set_current_user($userId);
    } finally {
      remove_filter('authenticate', $pickUser);
    }
  }

  /**
   * @inheritDoc
   */
  public function logoutSession() {
    wp_logout();
  }

  /**
   * @inheritDoc
   */
  public function loginStateless($userId) {
    wp_set_current_user($userId);
  }

  /**
   * @inheritDoc
   */
  public function getCurrentUserId() {
    $id = \get_current_user_id();
    return empty($id) ? NULL : $id;
  }

}
