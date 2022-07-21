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
 * CheckCredentialEvent examines a credential and (if it validly represents a
 * user-principal) then it reports the principal.
 */
class CheckCredentialEvent extends \Civi\Core\Event\GenericHookEvent {

  /**
   * Ex: 'Basic' or 'Bearer'
   *
   * @var string
   * @readonly
   */
  public $credFormat;

  /**
   * @var string
   * @readonly
   */
  public $credValue;

  /**
   * Authenticated principal.
   *
   * @var array|null
   */
  protected $principal = NULL;

  /**
   * Rejection message - If you know that this credential is intended for your listener,
   * and if it has some problem, then you can
   *
   * @var string|null
   */
  protected $rejection = NULL;

  /**
   * @param string $cred
   *   Ex: 'Basic ABCD1234' or 'Bearer ABCD1234'
   */
  public function __construct(string $cred) {
    [$this->credFormat, $this->credValue] = explode(' ', $cred, 2);
  }

  /**
   * Emphatically reject the credential.
   *
   * If you know that the credential is targeted at your provider, and if there is an error in it, then you
   * may set a rejection message. This will can provide more detailed debug information. However, it will
   * preclude other listeners from accepting the credential.
   *
   * @param string $message
   */
  public function reject(string $message): void {
    $this->rejection = $message;
  }

  /**
   * FIXME
   */
  public function accept(?array $principal): void {
    // .. FIXME: Do more validation like `acceptSub()` ...
    $this->principal = $principal;
    $this->stopPropagation();
  }

  public function getPrincipal(): ?array {
    return $this->principal;
  }

  public function getRejection(): ?string {
    return $this->rejection;
  }

}
