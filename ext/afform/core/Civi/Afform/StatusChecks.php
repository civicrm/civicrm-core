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

namespace Civi\Afform;

use CRM_Afform_ExtensionUtil as E;

class StatusChecks {

  /**
   * Afform has a soft dependency on Authx, which is used to generate authenticated email links.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see CRM_Utils_Hook::check()
   */
  public static function hook_civicrm_check($e) {
    $hasAuthx = \CRM_Extension_System::singleton()->getMapper()->isActiveModule('authx');
    $tokenFormCount = count(Tokens::getTokenForms());

    if ($hasAuthx && $tokenFormCount && !in_array('jwt', \Civi::settings()->get('authx_auto_cred'))) {
      $e->messages[] = new \CRM_Utils_Check_Message(
        'afform_token_authx',
        E::ts('Email token support has been configured for %1 form(s). This requires JWT authentication, <code>authx_auto_cred</code> does not include JWT. ', [
          1 => $tokenFormCount,
        ]),
        E::ts('AuthX Configuration'),
        \Psr\Log\LogLevel::ERROR,
        'fa-chain-broken'
      );

    }
  }

}
