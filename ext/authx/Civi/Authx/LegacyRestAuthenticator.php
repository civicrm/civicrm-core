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

use GuzzleHttp\Psr7\Response;

/**
 * Historically, 'extern/rest.php' and 'civicrm/ajax/rest' were similar interfaces
 * based on the same controller, but they used different authentication styles.
 *
 * This authenticator is activated if one requests 'civicrm/ajax/rest' using the
 * authentication style of 'extern/rest.php'.
 *
 * @package Civi\Authx
 */
class LegacyRestAuthenticator extends Authenticator {

  protected function reject($message = 'Authentication failed') {
    $data = ["error_message" => "FATAL: $message", "is_error" => 1];
    $r = new Response(200, ['Content-Type' => 'text/javascript'], json_encode($data));
    \CRM_Utils_System::sendResponse($r);
  }

  protected function login(AuthenticatorTarget $tgt) {
    parent::login($tgt);
    \Civi::dispatcher()->addListener('hook_civicrm_permission_check', function ($e) {
      if ($e->permission === 'access AJAX API') {
        $e->granted = TRUE;
      }
    });
  }

}
