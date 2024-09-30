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

namespace Civi\Standalone\Event;

use Civi\Core\Event\GenericHookEvent;

/**
 * Class LoginEvent
 *
 * This event (civi.standalone.login) is fired various times during the
 * standalone login process.
 *
 * Generally, listeners may set stopReason to a valid string (see below)
 * to prevent login continuing.
 */
class LoginEvent extends GenericHookEvent {

  /**
   * What stage are we at?
   *
   * Valid values:
   *
   * - 'pre_credentials_check'
   *
   *   userID should be set if the user exists but the password
   *   has not been checked yet. Example use: per IP/per user flood checks.
   *
   * - 'post_credentials_check'
   *
   *   userID must be set; password has been checked and stopReason
   *   should be 'wrongUserPassword' or NULL.
   *   Example use: limit incorrect password attempts per user.
   *
   * - 'post_mfa'
   *
   *   userID must be set; password was OK. stopReason should be
   *   'wrongMFA' (about to reject login)' or NULL (login about to happen).
   *   Example use: identify suspicious activity?
   *
   * - 'post_login'
   *
   *   userID is set; password and possibly MFA were correct. User is
   *   successfully logged in. Setting stopReason would have no effect.
   *   Example use: monitor logins.
   *
   * @var string
   */
  public $stage;

  /**
   * The user ID of the user attempting to login.
   *
   * NULL if the username provided was invalid.
   *
   * @var int
   */
  public $userID;

  /**
   * If set, authentication will not proceed.
   *
   * It may be set when the event is created or altered by listeners,
   * e.g. loginPrevented
   *
   * Valid values:
   * - 'wrongUserPassword'
   * - 'wrongMFA'
   * - 'loginPrevented'
   *
   * @var null|string
   */
  public $stopReason = NULL;

  /**
   * Class constructor.
   *
   * @param string $stage
   * @param int|null $userID
   * @param string|null $stopReason
   */
  public function __construct($stage, $userID, $stopReason = NULL) {
    $this->stage = $stage;
    $this->userID = $userID;
    $this->stopReason = $stopReason;
  }

  /**
   * @inheritDoc
   */
  public function getHookValues() {
    return [$this->stage, $this->userID, $this->stopReason];
  }

}
