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

use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Civi\Standalone\MFA\Base as MFABase;
use Civi\Standalone\Security;
use Civi\Standalone\Event\LogoutEvent;

/**
 * @class
 * This provides the Authx interface for Standalone.
 *
 * @service authx_standalone
 */
class Standalone extends AutoService implements AuthxInterface, EventSubscriberInterface {

  /**
   * @inheritdoc
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    // Priority is for this to be called before the password check.
    $events['civi.authx.checkCredential'][] = ['disallowBasicAuthIfMFAEnabled', CheckCredential::PRIORITY_BASIC_USER + 1];
    return $events;
  }

  /**
   * Disallow HTTP "Basic" credential as `username:password` when MFA is required.
   *
   * @param \Civi\Authx\CheckCredentialEvent $check
   */
  public function disallowBasicAuthIfMFAEnabled(CheckCredentialEvent $check): void {
    if ($check->credFormat === 'Basic') {
      if (MFABase::getAvailableClasses()) {
        $check->reject("Basic credentials not supported on systems with MFA enabled.");
      }
    }
  }

  /**
   * @inheritDoc
   */
  public function checkPassword(string $username, string $password) {
    $security = \Civi::service('standaloneusers.security');
    $cred = [
      'username' => $username,
      'password' => $password,
    ];
    $user = $security->loadUser($cred);
    return $user && $security->checkPassword($cred, $user) ? $user['id'] : NULL;
  }

  /**
   * @inheritDoc
   */
  public function loginSession($userId) {
    \session_regenerate_id(FALSE);

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

    session_regenerate_id(TRUE);
    \CRM_Core_Session::singleton()->reset();
    $_SESSION = [];
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
  public function logoutStateless() {
    global $loggedInUserId;
    $loggedInUserId = NULL;

    \CRM_Core_Session::singleton()->reset();
    $_SESSION = [];
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
    $user = \Civi\Api4\User::get(FALSE)
      ->addSelect('is_active')
      ->addWhere('id', '=', $userId)
      ->execute()
      ->first();

    // An unknown user is handled by the normal invalid-principal checks, while
    // an inactive Standalone user is blocked.
    return $user !== NULL && !$user['is_active'];
  }

}
