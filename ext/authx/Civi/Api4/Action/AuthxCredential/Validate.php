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

namespace Civi\Api4\Action\AuthxCredential;

use Civi\Api4\Generic\Result;

/**
 * Validate that a credential is still valid and can be used in CiviCRM.
 *
 * @method string getCred() Get Token to validate (required)
 * @method Validate setCred(string $token) Get contact ID param (required)
 */
class Validate extends \Civi\Api4\Generic\AbstractAction {

  /**
   * Identify the login-flow. Used for policy enforcement.
   *
   * @var string
   */
  protected $flow = 'script';

  /**
   * Credential to validate
   *
   * @var string
   *   Ex: 'Bearer ABCD1234'
   * @required
   */
  protected $cred;

  /**
   * @param \Civi\Api4\Generic\Result $result
   * @throws \Civi\Authx\AuthxException
   */
  public function _run(Result $result) {
    $details = [
      'flow' => $this->flow,
      'cred' => $this->cred,
      'siteKey' => NULL, /* Old school. Hopefully, we don't need to expose this. */
      'useSession' => FALSE,
    ];
    $auth = new \Civi\Authx\Authenticator();
    $auth->setRejectMode('exception');
    $result[] = $auth->validate($details);
  }

}
