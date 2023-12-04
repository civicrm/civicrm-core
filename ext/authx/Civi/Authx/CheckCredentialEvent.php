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
   * @var string
   *   Ex: 'civicrm/dashboard' or '*'
   *
   *   This identifies the path(s) that the requestor wants to access.
   *   For a stateless HTTP request, that's a specific path.
   *   For stateful HTTP session or CLI pipe, that's a wildcard.
   */
  protected $requestPath;

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
   * @param string $requestPath
   *   Ex: 'civicrm/dashboard' or '*'
   *
   *   This identifies the path(s) that the requestor wants to access.
   *   For a stateless HTTP request, that's a specific path.
   *   For stateful HTTP session or CLI pipe, that's a wildcard.
   */
  public function __construct(string $cred, string $requestPath) {
    [$this->credFormat, $this->credValue] = explode(' ', $cred, 2);
    $this->requestPath = $requestPath;
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

  /**
   * Accept the sub claim, matching the credentials to a specific user by
   * civicrm contact id ('contactId'), CRM user id ('userId') or CRM username
   * ('user'). This will cause authx to log in that user for the purposes of the
   * current request.
   *
   * The $principal must a mix of  of 'user', 'userId', 'contactId' and
   * 'credType':
   *
   * - 'credType': (string) type of credential used to identify the principal.
   *   ('pass', 'api_key', 'jwt')
   *
   * - 'contactId': (Authenticated) CiviCRM contact ID. If not specified, will
   *   be obtained from 'userId'.
   *
   * - 'userId': (Authenticated) UF user ID. If not specified, will be obtained
   *   from 'user' or 'contactId'.
   *
   * - 'user': (string). The username of the CMS user. Can be used instead of
   *   'userId'.
   *
   * - 'jwt': (Authenticated, Decoded) JWT claims (if applicable)
   *
   * Note: Event propogation will stop after this, so subscribers with lower
   * priorities will not be able to reject it.
   *
   * @param   array $principal Must include credType and (contactId or (userId
   * xor user))
   *
   */
  public function accept(array $principal): void {
    if (empty($principal['credType'])) {
      throw new AuthxException("Principal must specify credType");
    }

    if (empty($principal['contactId']) && empty($principal['userId']) && empty($principal['user'])) {
      throw new AuthxException("Principal must specify at least one of contactId, userId or user");
    }

    if (!empty($principal['userId']) && !empty($principal['user'])) {
      throw new AuthxException("Only userId or user can be specified in principal, not both");
    }

    $this->principal = $principal;
    $this->stopPropagation();
  }

  public function getPrincipal(): ?array {
    return $this->principal;
  }

  public function getRejection(): ?string {
    return $this->rejection;
  }

  /**
   * @return string
   *   Ex: 'civicrm/dashboard'
   */
  public function getRequestPath(): string {
    return $this->requestPath;
  }

}
