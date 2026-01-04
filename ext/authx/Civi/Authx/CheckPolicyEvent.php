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

/**
 * After we have determined that a credential appears authentic, we must check
 * whether our current policies allow this credential be used in this way.
 */
class CheckPolicyEvent extends \Civi\Core\Event\GenericHookEvent {

  /**
   * Describe the credential which we have accepted as authentic.
   *
   * @var \Civi\Authx\AuthenticatorTarget
   */
  public $target;

  /**
   * Describe whether we wish to authorize this credential to be used.
   *
   * @var array{userMode: string, allowCreds: string[], guards: string[]}
   */
  public $policy;


  /**
   * Rejection message - If you know that this credential is intended for your listener,
   * and if it has some problem, then you can
   *
   * @var string|null
   */
  protected $rejection = NULL;

  /**
   * @param array{userMode: string, allowCreds: string[], guards: string[]} $policy
   * @param \Civi\Authx\AuthenticatorTarget $target
   */
  public function __construct(array $policy, AuthenticatorTarget $target) {
    $this->policy = $policy;
    $this->target = $target;
  }

  public function getRejection(): ?string {
    return $this->rejection;
  }

  /**
   * Emphatically reject the credential.
   *
   * If you know that the credential is targeted at your provider, and if there
   * is an error in it, then you may set a rejection message. This will can
   * provide more detailed debug information. However, it will preclude other
   * listeners from accepting the credential.
   *
   * @param string $message
   */
  public function reject(string $message): void {
    $this->rejection = $message;
  }

}
