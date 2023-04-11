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
use Civi\Authx\AuthxException;
use Civi\Authx\AuthenticatorTarget;
use Civi\Authx\CheckCredentialEvent;

/**
 * Validate that a JWT is still valid and can be used in CiviCRM.
 *
 * @method int getToken() Get Token to validate (required)
 * @method setToken(string $token) Get contact ID param (required)
 */
class Validate extends \Civi\Api4\Generic\AbstractAction {
  /**
   * Token to validate
   *
   * @var string
   * @required
   */
  protected $token;

  /**
   * @param \Civi\Api4\Generic\Result $result
   */
  public function _run(Result $result) {
    $tgt = AuthenticatorTarget::create([
      'flow' => 'script',
      'cred' => 'Bearer ' . $this->token,
      'siteKey' => NULL,
      'useSession' => FALSE,
    ]);
    $checkEvent = new CheckCredentialEvent($tgt->cred);
    \Civi::dispatcher()->dispatch('civi.authx.checkCredential', $checkEvent);

    if ($checkEvent->getRejection()) {
      throw new AuthxException($checkEvent->getRejection());
    }

    $result[] = $checkEvent->getPrincipal();
  }

}
