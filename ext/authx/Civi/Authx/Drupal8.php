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

class Drupal8 implements AuthxInterface {

  /**
   * @inheritDoc
   */
  public function checkPassword(string $username, string $password) {
    $uid = \Drupal::service('user.auth')->authenticate($username, $password);
    // Ensure strict nullness.
    return $uid ?: NULL;
  }

  /**
   * @inheritDoc
   */
  public function loginSession($userId) {
    $user = \Drupal\user\Entity\User::load($userId);
    user_login_finalize($user);
  }

  /**
   * @inheritDoc
   */
  public function logoutSession() {
    user_logout();
  }

  /**
   * @inheritDoc
   */
  public function loginStateless($userId) {
    $user = \Drupal\user\Entity\User::load($userId);
    // In theory, we could use either account_switcher->switchTo() or current_user->setAccount().
    // switchTo() sounds more conscientious, but setAccount() might be a more accurate rendition
    // of "stateless login". At time of writing, there doesn't seem to be a compelling difference.
    // But if you're looking at this line while investigating some bug... then maybe there is?
    \Drupal::service('account_switcher')->switchTo($user);
  }

  /**
   * @inheritDoc
   */
  public function getCurrentUserId() {
    $user = \Drupal::currentUser();
    return $user && $user->getAccount()->id() ? $user->getAccount()->id() : NULL;
  }

  /**
   * @inheritDoc
   */
  public function getUserIsBlocked($userId) {
    $user = \Drupal\user\Entity\User::load($userId);
    // The user will not be blocked if there is no existence.
    if (!$user) {
      return FALSE;
    }
    return $user->isBlocked();
  }

}
